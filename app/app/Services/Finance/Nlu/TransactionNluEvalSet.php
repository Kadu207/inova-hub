<?php

namespace App\Services\Finance\Nlu;

/**
 * Eval set D21 — 50 frases PT-BR. Esperado: type, amount_cents, category_slug.
 *
 * @return list<array{text: string, type: string, amount_cents: int, category_slug: string}>
 */
final class TransactionNluEvalSet
{
    public static function cases(): array
    {
        return [
            // D17 base (20)
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
            // +30 D21
            ['text' => 'gastei 15 no lanche', 'type' => 'expense', 'amount_cents' => 1500, 'category_slug' => 'alimentacao'],
            ['text' => 'paguei 80 de táxi', 'type' => 'expense', 'amount_cents' => 8000, 'category_slug' => 'transporte'],
            ['text' => 'comprei remédio 42,30', 'type' => 'expense', 'amount_cents' => 4230, 'category_slug' => 'saude'],
            ['text' => 'spotify 34,90', 'type' => 'expense', 'amount_cents' => 3490, 'category_slug' => 'lazer'],
            ['text' => 'paguei condomínio 650', 'type' => 'expense', 'amount_cents' => 65000, 'category_slug' => 'moradia'],
            ['text' => 'gastei 120 no supermercado', 'type' => 'expense', 'amount_cents' => 12000, 'category_slug' => 'alimentacao'],
            ['text' => 'consulta médica 200', 'type' => 'expense', 'amount_cents' => 20000, 'category_slug' => 'saude'],
            ['text' => 'paguei 45 de pedágio', 'type' => 'expense', 'amount_cents' => 4500, 'category_slug' => 'transporte'],
            ['text' => 'udemy 89,90', 'type' => 'expense', 'amount_cents' => 8990, 'category_slug' => 'educacao'],
            ['text' => 'recebi 1200 de freelance', 'type' => 'income', 'amount_cents' => 120000, 'category_slug' => 'salario'],
            ['text' => 'gastei 70 no restaurante', 'type' => 'expense', 'amount_cents' => 7000, 'category_slug' => 'alimentacao'],
            ['text' => 'água 95 reais', 'type' => 'expense', 'amount_cents' => 9500, 'category_slug' => 'moradia'],
            ['text' => 'paguei 180 no teatro', 'type' => 'expense', 'amount_cents' => 18000, 'category_slug' => 'lazer'],
            ['text' => 'exame 320', 'type' => 'expense', 'amount_cents' => 32000, 'category_slug' => 'saude'],
            ['text' => 'gastei 25 na padaria', 'type' => 'expense', 'amount_cents' => 2500, 'category_slug' => 'alimentacao'],
            ['text' => 'combustível 310', 'type' => 'expense', 'amount_cents' => 31000, 'category_slug' => 'transporte'],
            ['text' => 'mensalidade escola 900', 'type' => 'expense', 'amount_cents' => 90000, 'category_slug' => 'educacao'],
            ['text' => 'ganhei 800 de freela', 'type' => 'income', 'amount_cents' => 80000, 'category_slug' => 'salario'],
            ['text' => 'paguei 60 no bar', 'type' => 'expense', 'amount_cents' => 6000, 'category_slug' => 'lazer'],
            ['text' => 'iptu 400', 'type' => 'expense', 'amount_cents' => 40000, 'category_slug' => 'moradia'],
            ['text' => 'gastei 33 no metrô', 'type' => 'expense', 'amount_cents' => 3300, 'category_slug' => 'transporte'],
            ['text' => 'hospital 1500', 'type' => 'expense', 'amount_cents' => 150000, 'category_slug' => 'saude'],
            ['text' => 'comprei material escolar 110', 'type' => 'expense', 'amount_cents' => 11000, 'category_slug' => 'educacao'],
            ['text' => 'R$ 22,00 lanche', 'type' => 'expense', 'amount_cents' => 2200, 'category_slug' => 'alimentacao'],
            ['text' => 'paguei 75 de passagem', 'type' => 'expense', 'amount_cents' => 7500, 'category_slug' => 'transporte'],
            ['text' => 'show 220 reais', 'type' => 'expense', 'amount_cents' => 22000, 'category_slug' => 'lazer'],
            ['text' => 'recebi 4200 de salário', 'type' => 'income', 'amount_cents' => 420000, 'category_slug' => 'salario'],
            ['text' => 'energia 340,20', 'type' => 'expense', 'amount_cents' => 34020, 'category_slug' => 'moradia'],
            ['text' => 'gastei 50 no parque', 'type' => 'expense', 'amount_cents' => 5000, 'category_slug' => 'lazer'],
            ['text' => 'paguei 95 na farmácia', 'type' => 'expense', 'amount_cents' => 9500, 'category_slug' => 'saude'],
        ];
    }
}
