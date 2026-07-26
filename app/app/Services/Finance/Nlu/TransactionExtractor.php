<?php

namespace App\Services\Finance\Nlu;

interface TransactionExtractor
{
    public function extract(string $text): ?ExtractedTransaction;
}
