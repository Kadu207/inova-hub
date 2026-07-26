<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class WebhookEvent extends Model
{
    use HasUuids;

    public const SOURCE_WHATSAPP = 'whatsapp';

    public const SOURCE_PLUGGY = 'pluggy';

    public const STATUS_RECEIVED = 'received';

    public const STATUS_PROCESSED = 'processed';

    public const STATUS_FAILED = 'failed';

    public const STATUS_DUPLICATE = 'duplicate';

    protected $fillable = [
        'source',
        'external_id',
        'status',
        'payload_meta',
        'last_error',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'payload_meta' => 'array',
            'processed_at' => 'datetime',
        ];
    }
}
