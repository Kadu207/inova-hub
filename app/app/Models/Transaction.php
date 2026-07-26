<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\TransactionFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    /** @use HasFactory<TransactionFactory> */
    use BelongsToOrganization, HasFactory, HasUuids;

    public const TYPE_EXPENSE = 'expense';

    public const TYPE_INCOME = 'income';

    public const SOURCE_MANUAL = 'manual';

    public const SOURCE_FINOVA = 'finova';

    public const SOURCE_OF = 'of';

    protected $fillable = [
        'organization_id',
        'category_id',
        'user_id',
        'amount_cents',
        'type',
        'currency',
        'source',
        'description',
        'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'amount_cents' => 'integer',
            'occurred_at' => 'datetime',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
