<?php

namespace App\Services\Finance\Nlu;

use App\Models\Category;
use App\Models\Transaction;
use App\Models\WhatsappIdentity;
use App\Services\Finance\Query\ResolvesTransactionQuery;
use App\Services\Finance\Query\SummarizesTransactionsForPeriod;
use App\Services\WhatsApp\FinovaCopy;
use App\Support\Tenancy\TenantContext;
use App\Support\WhatsApp\PhoneNormalizer;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

final class HandlesWhatsappTransactionText
{
    public function __construct(
        private readonly TransactionExtractor $extractor,
        private readonly PendingTransactionConfirmation $pending,
        private readonly ResolvesTransactionQuery $queryResolver,
        private readonly SummarizesTransactionsForPeriod $summarizer,
    ) {}

    /**
     * @return array{handled: bool, reply: ?string}
     */
    public function handle(string $fromPhone, string $text): array
    {
        $digits = preg_replace('/\D+/', '', $fromPhone) ?? '';
        $threshold = (float) config('services.finova.nlu_confidence_threshold', 0.75);

        $identity = $this->findIdentity($fromPhone);
        $normalized = mb_strtolower(trim($text));

        if ($this->isAffirmative($normalized) || $this->isNegative($normalized)) {
            $pendingTx = $this->pending->pull($digits);

            if ($pendingTx === null) {
                return ['handled' => false, 'reply' => null];
            }

            if ($this->isNegative($normalized)) {
                return ['handled' => true, 'reply' => FinovaCopy::transactionCancelled()];
            }

            if ($identity === null) {
                return ['handled' => true, 'reply' => FinovaCopy::transactionNeedsLink()];
            }

            $this->persist($identity, $pendingTx);

            return ['handled' => true, 'reply' => FinovaCopy::transactionSaved($pendingTx)];
        }

        $period = $this->queryResolver->handle($text);
        if ($period !== null) {
            if ($identity === null) {
                return ['handled' => true, 'reply' => FinovaCopy::transactionNeedsLink()];
            }

            TenantContext::set($identity->organization_id);
            try {
                $summary = $this->summarizer->handle($period);
            } finally {
                TenantContext::clear();
            }

            return ['handled' => true, 'reply' => FinovaCopy::transactionQuerySummary($summary)];
        }

        $extracted = $this->extractor->extract($text);

        if ($extracted === null) {
            return ['handled' => false, 'reply' => null];
        }

        if ($identity === null) {
            return ['handled' => true, 'reply' => FinovaCopy::transactionNeedsLink()];
        }

        if ($extracted->needsConfirmation($threshold)) {
            $this->pending->put($digits, $extracted);

            return ['handled' => true, 'reply' => FinovaCopy::transactionConfirmPrompt($extracted)];
        }

        $this->persist($identity, $extracted);

        return ['handled' => true, 'reply' => FinovaCopy::transactionSaved($extracted)];
    }

    private function persist(WhatsappIdentity $identity, ExtractedTransaction $extracted): void
    {
        TenantContext::set($identity->organization_id);

        try {
            $category = Category::query()
                ->where('slug', $extracted->categorySlug)
                ->where('kind', $extracted->type)
                ->first();

            if ($category === null) {
                $fallbackSlug = $extracted->type === Transaction::TYPE_INCOME ? 'salario' : 'outros';
                $category = Category::query()
                    ->where('slug', $fallbackSlug)
                    ->where('kind', $extracted->type)
                    ->firstOrFail();
            }

            Transaction::query()->create([
                'organization_id' => $identity->organization_id,
                'category_id' => $category->id,
                'user_id' => $identity->user_id,
                'amount_cents' => $extracted->amountCents,
                'type' => $extracted->type,
                'currency' => 'BRL',
                'source' => Transaction::SOURCE_FINOVA,
                'description' => $extracted->description,
                'occurred_at' => Carbon::parse($extracted->occurredOn ?? now()->toDateString())->startOfDay(),
            ]);

            Log::info('finova.transaction_created', [
                'organization_id' => $identity->organization_id,
                'type' => $extracted->type,
                'amount_cents' => $extracted->amountCents,
                'category' => $category->slug,
                'confidence' => $extracted->confidence,
            ]);
        } finally {
            TenantContext::clear();
        }
    }

    private function findIdentity(string $fromPhone): ?WhatsappIdentity
    {
        $candidates = array_values(array_unique(array_filter([
            PhoneNormalizer::toE164($fromPhone),
            str_starts_with($fromPhone, '+') ? $fromPhone : '+'.$fromPhone,
            '+'.(preg_replace('/\D+/', '', $fromPhone) ?? ''),
        ])));

        return WhatsappIdentity::query()
            ->withoutGlobalScopes()
            ->whereNull('revoked_at')
            ->whereIn('phone_e164', $candidates)
            ->first();
    }

    private function isAffirmative(string $normalized): bool
    {
        return preg_match('/^(sim|s|yes|confirmo|confirma|ok|pode|isso)\b/u', $normalized) === 1;
    }

    private function isNegative(string $normalized): bool
    {
        return preg_match('/^(n[aã]o|n|no|cancela|cancelar)\b/u', $normalized) === 1;
    }
}
