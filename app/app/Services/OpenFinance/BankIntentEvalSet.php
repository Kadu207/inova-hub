<?php

namespace App\Services\OpenFinance;

/**
 * Eval set D26 — frases que devem resolver para intents bancários OF.
 *
 * @phpstan-type Case array{text: string, intent: string}
 */
final class BankIntentEvalSet
{
    /**
     * @return list<Case>
     */
    public static function cases(): array
    {
        return [
            ['text' => 'Qual meu saldo?', 'intent' => BankIntent::Balance->value],
            ['text' => 'qual é o meu saldo', 'intent' => BankIntent::Balance->value],
            ['text' => 'meu saldo', 'intent' => BankIntent::Balance->value],
            ['text' => 'saldo', 'intent' => BankIntent::Balance->value],
            ['text' => 'saldo do banco', 'intent' => BankIntent::Balance->value],
            ['text' => 'saldo da conta', 'intent' => BankIntent::Balance->value],
            ['text' => 'quanto tenho na conta', 'intent' => BankIntent::Balance->value],
            ['text' => 'quanto tenho', 'intent' => BankIntent::Balance->value],
            ['text' => 'saldo open finance', 'intent' => BankIntent::Balance->value],
            ['text' => 'meu saldo pluggy', 'intent' => BankIntent::Balance->value],
            ['text' => 'ver extrato', 'intent' => BankIntent::Statement->value],
            ['text' => 'extrato do banco', 'intent' => BankIntent::Statement->value],
            ['text' => 'últimas transações', 'intent' => BankIntent::Statement->value],
            ['text' => 'ultimas movimentacoes', 'intent' => BankIntent::Statement->value],
            ['text' => 'mostra o extrato', 'intent' => BankIntent::Statement->value],
            ['text' => 'meus cartões', 'intent' => BankIntent::Cards->value],
            ['text' => 'cartao de credito', 'intent' => BankIntent::Cards->value],
            ['text' => 'fatura do cartão', 'intent' => BankIntent::Cards->value],
            ['text' => 'limite do cartao', 'intent' => BankIntent::Cards->value],
            ['text' => 'quais cartoes tenho', 'intent' => BankIntent::Cards->value],
        ];
    }

    /**
     * @return list<string>
     */
    public static function negatives(): array
    {
        return [
            'gastei 45 no almoço',
            'quanto gastei essa semana?',
            'resumo do mês',
            'oi',
            'ajuda',
        ];
    }
}
