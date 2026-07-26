<?php

namespace App\Services\OpenFinance;

enum BankIntent: string
{
    case Balance = 'bank.balance';
    case Statement = 'bank.statement';
    case Cards = 'bank.cards';
}
