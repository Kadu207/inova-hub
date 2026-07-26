<?php

namespace App\Services\Finance\Nlu;

use App\Models\Transaction;
use Carbon\Carbon;

/**
 * Extrator determinístico PT-BR (funciona sem LLM). Meta D21: ≥85% no eval set (50).
 */
final class HeuristicTransactionExtractor implements TransactionExtractor
{
    /** @var array<string, list<string>> */
    private const CATEGORY_KEYWORDS = [
        'moradia' => ['aluguel', 'condominio', 'condomínio', 'luz', 'energia', 'agua', 'água', 'internet', 'gás', 'iptu', 'reforma'],
        'alimentacao' => ['almoço', 'almoco', 'jantar', 'café', 'cafe', 'mercado', 'ifood', 'padaria', 'restaurante', 'lanche', 'comida', 'supermercado', 'açougue', 'acougue'],
        'transporte' => ['uber', 'taxi', 'táxi', 'gasolina', 'estacionamento', 'ônibus', 'onibus', 'metrô', 'metro', 'combustível', 'combustivel', 'pedágio', 'pedagio', 'passagem'],
        'saude' => ['farmácia', 'farmacia', 'médico', 'medico', 'dentista', 'remédio', 'remedio', 'hospital', 'plano de saúde', 'plano de saude', 'exame', 'consulta'],
        'lazer' => ['cinema', 'bar', 'show', 'netflix', 'spotify', 'viagem', 'streaming', 'teatro', 'parque'],
        'educacao' => ['curso', 'escola', 'faculdade', 'livro', 'mensalidade', 'udemy', 'faculdade', 'material escolar'],
        'salario' => ['salário', 'salario', 'proventos', 'holerite', 'freelance', 'freela'],
    ];

    public function extract(string $text): ?ExtractedTransaction
    {
        $normalized = mb_strtolower(trim($text));
        $normalized = preg_replace('/\s+/', ' ', $normalized) ?? $normalized;

        if ($normalized === '' || preg_match('/^\d{6}$/', $normalized) === 1) {
            return null;
        }

        // Falsos positivos: greeting/help/query sem valor de lançamento
        if ($this->looksLikeNonTransaction($normalized)) {
            return null;
        }

        // Backlog D21: USD / $ sem conversão — não gravar como BRL
        if (preg_match('/\b(usd|d[oó]lar(?:es)?|\$)\b/u', $normalized) === 1) {
            return null;
        }

        // Parcelas / cartão em N vezes — ambíguo (backlog)
        if (preg_match('/\b(\d+)\s*x\b|\bparcelad[oa]s?\b|\bem\s+\d+\s+vezes\b/u', $normalized) === 1) {
            return null;
        }

        $amount = $this->parseAmountCents($normalized);
        if ($amount === null || $amount < 1) {
            return null;
        }

        // Evita ano sozinho (2024–2030) sem cue monetário
        if ($amount >= 202000 && $amount <= 203099 && ! $this->hasExplicitMoneyCue($normalized)) {
            return null;
        }

        $type = $this->detectType($normalized);
        [$slug, $categoryConfidence] = $this->detectCategory($normalized, $type);

        $confidence = 0.55;
        if ($categoryConfidence >= 0.9) {
            $confidence = 0.92;
        } elseif ($categoryConfidence >= 0.7) {
            $confidence = 0.8;
        }

        if ($this->hasExplicitMoneyCue($normalized)) {
            $confidence = min(0.99, $confidence + 0.05);
        }

        $description = $this->buildDescription($text, $slug);

        return new ExtractedTransaction(
            type: $type,
            amountCents: $amount,
            categorySlug: $slug,
            confidence: $confidence,
            description: $description,
            occurredOn: Carbon::now()->toDateString(),
        );
    }

    private function looksLikeNonTransaction(string $normalized): bool
    {
        if (preg_match('/\b(oi|ol[aá]|ajuda|help|menu|obrigad[oa]|valeu)\b/u', $normalized) === 1
            && $this->parseAmountCents($normalized) === null) {
            return true;
        }

        if (preg_match('/\b(quanto|quantos|resumo|totais?|saldo)\b/u', $normalized) === 1
            && preg_match('/\b(gastei|paguei|comprei|recebi|ganhei)\s+\d/u', $normalized) !== 1) {
            return true;
        }

        return false;
    }

    private function detectType(string $normalized): string
    {
        if (preg_match('/\b(recebi|ganhei|entrou|depositaram|renda|sal[aá]rio|proventos|freelance|freela)\b/u', $normalized) === 1) {
            return Transaction::TYPE_INCOME;
        }

        if (preg_match('/\b(receita|entrada de)\b/u', $normalized) === 1) {
            return Transaction::TYPE_INCOME;
        }

        return Transaction::TYPE_EXPENSE;
    }

    /**
     * @return array{0: string, 1: float}
     */
    private function detectCategory(string $normalized, string $type): array
    {
        if ($type === Transaction::TYPE_INCOME) {
            foreach (self::CATEGORY_KEYWORDS['salario'] as $keyword) {
                if (str_contains($normalized, $keyword)) {
                    return ['salario', 0.95];
                }
            }

            if (str_contains($normalized, 'pagamento') && preg_match('/\b(recebi|ganhei)\b/u', $normalized) === 1) {
                return ['salario', 0.9];
            }

            return ['salario', 0.7];
        }

        $bestSlug = 'outros';
        $bestScore = 0.0;

        foreach (self::CATEGORY_KEYWORDS as $slug => $keywords) {
            if ($slug === 'salario') {
                continue;
            }

            foreach ($keywords as $keyword) {
                if (preg_match('/\b'.preg_quote($keyword, '/').'\b/u', $normalized) === 1) {
                    $score = mb_strlen($keyword) >= 5 ? 0.95 : 0.85;
                    if ($score > $bestScore) {
                        $bestScore = $score;
                        $bestSlug = $slug;
                    }
                }
            }
        }

        return [$bestSlug, $bestScore > 0 ? $bestScore : 0.5];
    }

    private function parseAmountCents(string $normalized): ?int
    {
        if (preg_match('/r\$\s*(\d{1,3}(?:\.\d{3})*,\d{2}|\d+,\d{2}|\d+(?:\.\d{2})?)/u', $normalized, $m) === 1) {
            return $this->toCents($m[1]);
        }

        if (preg_match('/\b(\d{1,3}(?:\.\d{3})*,\d{2})\b/u', $normalized, $m) === 1) {
            return $this->toCents($m[1]);
        }

        if (preg_match('/\b(?:gastei|paguei|comprei|recebi|ganhei|despesa|custo)\s+(?:r\$\s*)?(\d+(?:[.,]\d{1,2})?)\b/u', $normalized, $m) === 1) {
            return $this->toCents($m[1]);
        }

        if (preg_match('/\b(\d+(?:[.,]\d{1,2})?)\s*(?:reais|real)\b/u', $normalized, $m) === 1) {
            return $this->toCents($m[1]);
        }

        if (preg_match('/\b(\d+(?:[.,]\d{1,2})?)\s+(?:no|na|de|do|da|em|pro|pra|para)\b/u', $normalized, $m) === 1) {
            return $this->toCents($m[1]);
        }

        foreach (self::CATEGORY_KEYWORDS as $keywords) {
            foreach ($keywords as $keyword) {
                $quoted = preg_quote($keyword, '/');
                if (preg_match('/\b'.$quoted.'\b\s+(\d+(?:[.,]\d{1,2})?)\b/u', $normalized, $m) === 1) {
                    return $this->toCents($m[1]);
                }
                if (preg_match('/\b(\d+(?:[.,]\d{1,2})?)\s+'.$quoted.'\b/u', $normalized, $m) === 1) {
                    return $this->toCents($m[1]);
                }
            }
        }

        if (preg_match('/(?:gastei|paguei|comprei|recebi|ganhei).+?\b(\d+(?:[.,]\d{1,2})?)\s*$/u', $normalized, $m) === 1) {
            return $this->toCents($m[1]);
        }

        return null;
    }

    private function toCents(string $raw): int
    {
        $value = trim($raw);

        if (str_contains($value, ',')) {
            $value = str_replace('.', '', $value);
            $value = str_replace(',', '.', $value);
        }

        if (! is_numeric($value)) {
            return 0;
        }

        return (int) round(((float) $value) * 100);
    }

    private function hasExplicitMoneyCue(string $normalized): bool
    {
        return preg_match('/\b(gastei|paguei|comprei|recebi|ganhei|r\$|reais|despesa|receita|custo)\b/u', $normalized) === 1;
    }

    private function buildDescription(string $original, string $slug): string
    {
        $clean = trim(preg_replace('/\s+/', ' ', $original) ?? $original);

        if (mb_strlen($clean) > 80) {
            $clean = mb_substr($clean, 0, 77).'...';
        }

        return $clean !== '' ? $clean : $slug;
    }
}
