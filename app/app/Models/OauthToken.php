<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OauthToken extends Model
{
    use BelongsToOrganization, HasUuids;

    public const PROVIDER_GOOGLE = 'google';

    protected $table = 'oauth_tokens';

    protected $fillable = [
        'organization_id',
        'user_id',
        'provider',
        'provider_account_email',
        'access_token',
        'refresh_token',
        'expires_at',
        'scopes',
        'consent_version',
        'connected_at',
        'revoked_at',
    ];

    protected $hidden = [
        'access_token',
        'refresh_token',
    ];

    protected function casts(): array
    {
        return [
            'access_token' => 'encrypted',
            'refresh_token' => 'encrypted',
            'expires_at' => 'datetime',
            'scopes' => 'array',
            'connected_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isActive(): bool
    {
        return $this->revoked_at === null && filled($this->access_token);
    }
}
