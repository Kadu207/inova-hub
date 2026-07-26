<?php

namespace App\Services\Finance\Query;

enum TransactionPeriod: string
{
    case Today = 'today';
    case Week = 'week';
    case Month = 'month';

    public function label(): string
    {
        return match ($this) {
            self::Today => 'hoje',
            self::Week => 'esta semana',
            self::Month => 'este mês',
        };
    }
}
