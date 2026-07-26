<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OfItem extends Model
{
    use BelongsToOrganization, HasUuids;

    public const STATUS_CREATED = 'CREATED';

    public const STATUS_UPDATED = 'UPDATED';

    public const STATUS_DELETED = 'DELETED';

    public const STATUS_LOGIN_ERROR = 'LOGIN_ERROR';

    protected $table = 'of_items';

    protected $fillable = [
        'organization_id',
        'user_id',
        'pluggy_item_id',
        'status',
        'client_user_id',
        'connector_name',
        'consent_at',
    ];

    protected function casts(): array
    {
        return [
            'consent_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function accounts(): HasMany
    {
        return $this->hasMany(OfAccount::class, 'of_item_id');
    }
}
