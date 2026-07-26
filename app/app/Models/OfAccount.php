<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OfAccount extends Model
{
    use BelongsToOrganization, HasUuids;

    protected $table = 'of_accounts';

    protected $fillable = [
        'organization_id',
        'of_item_id',
        'pluggy_account_id',
        'name',
        'type',
        'subtype',
        'number',
        'currency',
        'balance_cents',
        'synced_at',
    ];

    protected function casts(): array
    {
        return [
            'balance_cents' => 'integer',
            'synced_at' => 'datetime',
        ];
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(OfItem::class, 'of_item_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(OfTransaction::class, 'of_account_id');
    }
}
