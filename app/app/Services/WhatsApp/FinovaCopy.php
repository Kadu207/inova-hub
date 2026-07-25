<?php

namespace App\Services\WhatsApp;

final class FinovaCopy
{
    public static function greeting(): string
    {
        return 'Olá! Eu sou a Finova, assistente do Inova Hub. Finanças, agenda e equipe no WhatsApp. Digite *ajuda* para ver o que eu já faço.';
    }

    public static function help(): string
    {
        return "Eu sou a Finova. Por enquanto você pode:\n"
            ."• dizer *oi* — eu me apresento\n"
            ."• enviar um código de 6 dígitos do Hub — vinculo seu WhatsApp\n"
            ."• digitar *ajuda* — este menu\n\n"
            .'Em breve: lançamentos, agenda e tarefas por aqui.';
    }

    public static function fallback(): string
    {
        return 'Recebi sua mensagem. Ainda estou aprendendo. Digite *ajuda* para ver o que já funciona, ou vincule seu número pelo Inova Hub.';
    }

    public static function otpLinked(): string
    {
        return 'Pronto! Seu WhatsApp foi vinculado ao Inova Hub. Pode usar a Finova por aqui.';
    }

    public static function otpFailed(): string
    {
        return 'Não consegui validar esse código. Gere um novo OTP no Inova Hub (menu WhatsApp) e envie só os 6 dígitos.';
    }
}
