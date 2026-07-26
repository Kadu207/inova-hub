<?php

namespace App\Services\OpenFinance;

use App\Models\OfTransaction;

/**
 * Heurística PT-BR para sugerir categoria em transações OF (editável no Hub).
 */
final class CategorizesOfTransactions
{
    /** @var array<string, string> */
    public const LABELS = [
        'alimentacao' => 'Alimentação',
        'transporte' => 'Transporte',
        'moradia' => 'Moradia',
        'saude' => 'Saúde',
        'lazer' => 'Lazer',
        'educacao' => 'Educação',
        'salario' => 'Salário',
        'outros' => 'Outros',
    ];

    /** @var array<string, list<string>> */
    private const KEYWORDS = [
        'alimentacao' => ['almoço', 'almoco', 'jantar', 'café', 'cafe', 'mercado', 'ifood', 'padaria', 'restaurante', 'lanche', 'supermercado', 'rappi', 'delivery'],
        'transporte' => ['uber', '99', 'taxi', 'táxi', 'gasolina', 'estacionamento', 'ônibus', 'onibus', 'metrô', 'metro', 'combustível', 'pedágio', 'shell', 'ipiranga'],
        'moradia' => ['aluguel', 'condominio', 'condomínio', 'luz', 'energia', 'agua', 'água', 'internet', 'gás', 'iptu', 'claro', 'vivo', 'tim'],
        'saude' => ['farmácia', 'farmacia', 'médico', 'medico', 'dentista', 'remédio', 'hospital', 'drogasil', 'raia'],
        'lazer' => ['cinema', 'bar', 'netflix', 'spotify', 'steam', 'playstation', 'ingresso', 'disney'],
        'educacao' => ['curso', 'escola', 'faculdade', 'udemy', 'livro', 'mensalidade'],
        'salario' => ['salário', 'salario', 'proventos', 'freelance', 'pix recebido', 'transferência recebida'],
    ];

    /**
     * @return int number of rows updated
     */
    public function handle(?string $organizationId = null, int $limit = 200): int
    {
        $query = OfTransaction::query()
            ->where(function ($q) {
                $q->whereNull('category_suggested')
                    ->orWhere('category_suggested', '');
            })
            ->where('category_manual', false)
            ->orderByDesc('occurred_at')
            ->limit($limit);

        if ($organizationId !== null) {
            $query->where('organization_id', $organizationId);
        }

        $updated = 0;
        foreach ($query->get() as $tx) {
            $slug = $this->suggest($tx->description ?? '', $tx->type);
            if ($slug === null) {
                continue;
            }
            $tx->update(['category_suggested' => $slug]);
            $updated++;
        }

        return $updated;
    }

    public function suggest(string $description, string $type): ?string
    {
        $normalized = mb_strtolower(trim($description));
        if ($normalized === '') {
            return $type === 'income' ? 'salario' : 'outros';
        }

        foreach (self::KEYWORDS as $slug => $words) {
            foreach ($words as $word) {
                if (str_contains($normalized, mb_strtolower($word))) {
                    return $slug;
                }
            }
        }

        return $type === 'income' ? 'salario' : 'outros';
    }

    public static function label(?string $slug): string
    {
        if ($slug === null || $slug === '') {
            return '—';
        }

        return self::LABELS[$slug] ?? $slug;
    }
}
