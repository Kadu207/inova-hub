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
}
