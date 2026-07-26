<?php

namespace App\Services\Finance\Nlu;

/**
 * Eval set D17 — 20 frases PT-BR. Esperado: type, amount_cents, category_slug.
 *
 * @return list<array{text: string, type: string, amount_cents: int, category_slug: string}>
 */
final class TransactionNluEvalSet
{
    public static function cases(): array
    {
        return [
            ['text' => 'gastei 45 no almoço', 'type' => 'expense', 'amount_cents' => 4500, 'category_slug' => 'alimentacao'],
            ['text' => 'paguei 120 de uber', 'type' => 'expense', 'amount_cents' => 12000, 'category_slug' => 'transporte'],
            ['text' => 'comprei no mercado 89,90', 'type' => 'expense', 'amount_cents' => 8990, 'category_slug' => 'alimentacao'],
            ['text' => 'R$ 35,50 ifood', 'type' => 'expense', 'amount_cents' => 3550, 'category_slug' => 'alimentacao'],
            ['text' => 'gastei 200 na gasolina', 'type' => 'expense', 'amount_cents' => 20000, 'category_slug' => 'transporte'],
            ['text' => 'paguei 1500 de aluguel', 'type' => 'expense', 'amount_cents' => 150000, 'category_slug' => 'moradia'],
            ['text' => 'conta de luz 210,45', 'type' => 'expense', 'amount_cents' => 21045, 'category_slug' => 'moradia'],
            ['text' => 'farmácia 67 reais', 'type' => 'expense', 'amount_cents' => 6700, 'category_slug' => 'saude'],
            ['text' => 'gastei 80 no cinema', 'type' => 'expense', 'amount_cents' => 8000, 'category_slug' => 'lazer'],
            ['text' => 'paguei 300 no curso', 'type' => 'expense', 'amount_cents' => 30000, 'category_slug' => 'educacao'],
            ['text' => 'recebi 3500 de salário', 'type' => 'income', 'amount_cents' => 350000, 'category_slug' => 'salario'],
            ['text' => 'ganhei 500 reais', 'type' => 'income', 'amount_cents' => 50000, 'category_slug' => 'salario'],
            ['text' => 'despesa 40 no café', 'type' => 'expense', 'amount_cents' => 4000, 'category_slug' => 'alimentacao'],
            ['text' => 'paguei 99 no estacionamento', 'type' => 'expense', 'amount_cents' => 9900, 'category_slug' => 'transporte'],
            ['text' => 'gastei 55 no jantar', 'type' => 'expense', 'amount_cents' => 5500, 'category_slug' => 'alimentacao'],
            ['text' => 'netflix 55,90', 'type' => 'expense', 'amount_cents' => 5590, 'category_slug' => 'lazer'],
            ['text' => 'dentista 250', 'type' => 'expense', 'amount_cents' => 25000, 'category_slug' => 'saude'],
            ['text' => 'internet 120 reais', 'type' => 'expense', 'amount_cents' => 12000, 'category_slug' => 'moradia'],
            ['text' => 'comprei livro 90', 'type' => 'expense', 'amount_cents' => 9000, 'category_slug' => 'educacao'],
            ['text' => 'recebi pagamento 2800', 'type' => 'income', 'amount_cents' => 280000, 'category_slug' => 'salario'],
        ];
    }
}
