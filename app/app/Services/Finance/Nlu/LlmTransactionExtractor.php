<?php

namespace App\Services\Finance\Nlu;

use App\Models\Transaction;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Extrator via API compatível com OpenAI (OpenAI ou Groq).
 */
final class LlmTransactionExtractor implements TransactionExtractor
{
    public function __construct(
        private readonly HeuristicTransactionExtractor $fallback,
    ) {}

    public function extract(string $text): ?ExtractedTransaction
    {
        $apiKey = (string) config('services.llm.api_key');
        $baseUrl = rtrim((string) config('services.llm.base_url'), '/');
        $model = (string) config('services.llm.model');

        if ($apiKey === '' || $baseUrl === '') {
            return $this->fallback->extract($text);
        }

        try {
            $response = Http::withToken($apiKey)
                ->acceptJson()
                ->timeout(20)
                ->post($baseUrl.'/chat/completions', [
                    'model' => $model,
                    'temperature' => 0,
                    'response_format' => ['type' => 'json_object'],
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => $this->systemPrompt(),
                        ],
                        [
                            'role' => 'user',
                            'content' => $text,
                        ],
                    ],
                ]);

            if (! $response->successful()) {
                Log::warning('finova.nlu_llm_http_failed', ['status' => $response->status()]);

                return $this->fallback->extract($text);
            }

            $content = (string) data_get($response->json(), 'choices.0.message.content', '');
            $parsed = json_decode($content, true);

            if (! is_array($parsed) || ($parsed['is_transaction'] ?? false) !== true) {
                return $this->fallback->extract($text);
            }

            $type = ($parsed['type'] ?? '') === 'income'
                ? Transaction::TYPE_INCOME
                : Transaction::TYPE_EXPENSE;

            $amountCents = (int) ($parsed['amount_cents'] ?? 0);
            if ($amountCents < 1) {
                return $this->fallback->extract($text);
            }

            $slug = (string) ($parsed['category_slug'] ?? 'outros');
            $allowed = ['moradia', 'alimentacao', 'transporte', 'saude', 'lazer', 'educacao', 'salario', 'outros'];
            if (! in_array($slug, $allowed, true)) {
                $slug = $type === Transaction::TYPE_INCOME ? 'salario' : 'outros';
            }

            $confidence = (float) ($parsed['confidence'] ?? 0.7);
            $confidence = max(0.0, min(1.0, $confidence));

            return new ExtractedTransaction(
                type: $type,
                amountCents: $amountCents,
                categorySlug: $slug,
                confidence: $confidence,
                description: is_string($parsed['description'] ?? null) ? $parsed['description'] : $text,
                occurredOn: is_string($parsed['occurred_on'] ?? null) ? $parsed['occurred_on'] : now()->toDateString(),
            );
        } catch (Throwable $e) {
            Log::warning('finova.nlu_llm_exception', ['message' => $e->getMessage()]);

            return $this->fallback->extract($text);
        }
    }

    private function systemPrompt(): string
    {
        return <<<'PROMPT'
Você extrai lançamentos financeiros em português do Brasil.
Responda SOMENTE JSON com:
{
  "is_transaction": boolean,
  "type": "expense"|"income",
  "amount_cents": number,
  "category_slug": "moradia"|"alimentacao"|"transporte"|"saude"|"lazer"|"educacao"|"salario"|"outros",
  "confidence": number between 0 and 1,
  "description": string,
  "occurred_on": "YYYY-MM-DD"|null
}
Moeda BRL. Se não for lançamento, is_transaction=false.
PROMPT;
    }
}
