<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConsentLog extends Model
{
    use BelongsToOrganization, HasUuids;

    public const TYPE_OPEN_FINANCE = 'open_finance';

    protected $table = 'consent_logs';

    protected $fillable = [
        'organization_id',
        'user_id',
        'type',
        'version',
        'accepted_at',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'accepted_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
