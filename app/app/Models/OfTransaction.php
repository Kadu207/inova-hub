<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OfTransaction extends Model
{
    use BelongsToOrganization, HasUuids;

    public const TYPE_EXPENSE = 'expense';

    public const TYPE_INCOME = 'income';

    protected $table = 'of_transactions';

    protected $fillable = [
        'organization_id',
        'of_account_id',
        'pluggy_transaction_id',
        'amount_cents',
        'currency',
        'type',
        'description',
        'category_suggested',
        'category_manual',
        'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'amount_cents' => 'integer',
            'category_manual' => 'boolean',
            'occurred_at' => 'datetime',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(OfAccount::class, 'of_account_id');
    }
}
