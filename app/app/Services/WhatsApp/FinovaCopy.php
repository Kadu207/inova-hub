<?php

namespace App\Services\WhatsApp;

use App\Services\Finance\Nlu\ExtractedTransaction;

final class FinovaCopy
{
    public static function greeting(): string
    {
        return 'Olá! Eu sou a Finova, assistente do Inova Hub. Finanças, agenda e equipe no WhatsApp. Digite *ajuda* para ver o que eu já faço.';
    }

    public static function help(): string
    {
        return "Eu sou a Finova. Você pode:\n"
            ."• dizer *oi*\n"
            ."• enviar um código de 6 dígitos do Hub — vinculo seu WhatsApp\n"
            ."• lançar gastos/receitas em *texto* ou *áudio*, ex.: *gastei 45 no almoço*\n"
            ."• consultar lançamentos: *quanto gastei essa semana?* (hoje / semana / mês)\n"
            ."• banco (Open Finance): *qual meu saldo?*, *extrato*, *meus cartões*\n"
            ."• digitar *ajuda* — este menu\n\n"
            .'Se eu ficar na dúvida, peço confirmação (sim/não) antes de gravar.';
    }

    public static function fallback(): string
    {
        return 'Recebi sua mensagem. Ainda estou aprendendo. Digite *ajuda* ou envie algo como *gastei 30 no uber*.';
    }

    public static function otpLinked(): string
    {
        return 'Pronto! Seu WhatsApp foi vinculado ao Inova Hub. Pode usar a Finova por aqui.';
    }

    public static function otpFailed(): string
    {
        return 'Não consegui validar esse código. Gere um novo OTP no Inova Hub (menu WhatsApp) e envie só os 6 dígitos.';
    }

    public static function transactionNeedsLink(): string
    {
        return 'Para lançar gastos por aqui, vincule seu WhatsApp no Inova Hub (menu WhatsApp → OTP).';
    }

    public static function transactionConfirmPrompt(ExtractedTransaction $tx): string
    {
        $valor = 'R$ '.number_format($tx->amountCents / 100, 2, ',', '.');
        $tipo = $tx->type === 'income' ? 'receita' : 'despesa';

        return "Confirma {$tipo} de {$valor} em *{$tx->categorySlug}*? Responda *sim* ou *não*.";
    }

    public static function transactionSaved(ExtractedTransaction $tx): string
    {
        $valor = 'R$ '.number_format($tx->amountCents / 100, 2, ',', '.');
        $tipo = $tx->type === 'income' ? 'Receita' : 'Despesa';

        return "{$tipo} de {$valor} registrada em *{$tx->categorySlug}*. Pode ver no Hub → Lançamentos.";
    }

    public static function transactionCancelled(): string
    {
        return 'Ok, cancelei. Nada foi gravado.';
    }

    public static function audioNeedsStt(): string
    {
        return 'Recebi seu áudio, mas a transcrição (Whisper) ainda não está configurada. Peça ao admin para definir OPENAI_API_KEY ou GROQ_API_KEY, ou envie o gasto em texto.';
    }

    public static function audioFailed(): string
    {
        return 'Não consegui processar esse áudio. Tente de novo ou envie em texto, ex.: *gastei 30 no uber*.';
    }

    /**
     * @param  array{
     *   period: \App\Services\Finance\Query\TransactionPeriod,
     *   expense_cents: int,
     *   income_cents: int,
     *   net_cents: int,
     *   top_categories: list<array{name: string, amount_cents: int}>
     * }  $summary
     */
    public static function transactionQuerySummary(array $summary): string
    {
        $periodLabel = $summary['period']->label();
        $expense = 'R$ '.number_format($summary['expense_cents'] / 100, 2, ',', '.');
        $income = 'R$ '.number_format($summary['income_cents'] / 100, 2, ',', '.');
        $net = 'R$ '.number_format($summary['net_cents'] / 100, 2, ',', '.');

        $lines = [
            "Resumo *{$periodLabel}*:",
            "• Despesas: {$expense}",
            "• Receitas: {$income}",
            "• Saldo: {$net}",
        ];

        if ($summary['top_categories'] !== []) {
            $lines[] = 'Top categorias:';
            foreach ($summary['top_categories'] as $row) {
                $valor = 'R$ '.number_format($row['amount_cents'] / 100, 2, ',', '.');
                $lines[] = "• {$row['name']}: {$valor}";
            }
        }

        return implode("\n", $lines);
    }

    public static function bankNeedsConnection(): string
    {
        return 'Ainda não há banco conectado. No Inova Hub → Bancos, use *Conectar banco* (Pluggy) e depois pergunte de novo.';
    }

    /**
     * @param  array{
     *   total_balance_cents: int,
     *   accounts: list<array{name: string, balance_cents: int, is_card: bool}>
     * }  $summary
     */
    public static function bankBalance(array $summary): string
    {
        if ($summary['accounts'] === []) {
            return 'Banco conectado, mas ainda sem contas sincronizadas. No Hub → Bancos, toque em *Sincronizar*.';
        }

        $total = 'R$ '.number_format($summary['total_balance_cents'] / 100, 2, ',', '.');
        $lines = ["Saldo Open Finance (somente leitura): *{$total}*"];

        foreach (array_slice($summary['accounts'], 0, 5) as $account) {
            $valor = 'R$ '.number_format($account['balance_cents'] / 100, 2, ',', '.');
            $lines[] = "• {$account['name']}: {$valor}";
        }

        if (count($summary['accounts']) > 5) {
            $lines[] = '… e mais no Hub → Bancos.';
        }

        return implode("\n", $lines);
    }

    /**
     * @param  array{
     *   recent_transactions: list<array{description: string, amount_cents: int, type: string, occurred_at: string, account_name: string}>
     * }  $summary
     */
    public static function bankStatement(array $summary): string
    {
        if ($summary['recent_transactions'] === []) {
            return 'Não há movimentações OF sincronizadas ainda. No Hub → Bancos, toque em *Sincronizar* ou abra o extrato da conta.';
        }

        $lines = ['Últimas movimentações (Open Finance):'];
        foreach ($summary['recent_transactions'] as $tx) {
            $sign = $tx['type'] === 'income' ? '+' : '-';
            $valor = 'R$ '.number_format($tx['amount_cents'] / 100, 2, ',', '.');
            $lines[] = "• {$tx['occurred_at']} {$tx['description']}: {$sign}{$valor}";
        }

        $lines[] = 'Detalhes no Hub → Bancos.';

        return implode("\n", $lines);
    }

    /**
     * @param  array{
     *   cards: list<array{name: string, balance_cents: int}>
     * }  $summary
     */
    public static function bankCards(array $summary): string
    {
        if ($summary['cards'] === []) {
            return 'Não encontrei cartões de crédito nas contas OF sincronizadas. Se o banco tiver cartão, sincronize de novo no Hub → Bancos.';
        }

        $lines = ['Cartões (Open Finance, somente leitura):'];
        foreach ($summary['cards'] as $card) {
            $valor = 'R$ '.number_format($card['balance_cents'] / 100, 2, ',', '.');
            $lines[] = "• {$card['name']}: {$valor}";
        }

        return implode("\n", $lines);
    }
}
