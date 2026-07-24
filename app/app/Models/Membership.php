<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\MembershipFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Membership extends Model
{
    /** @use HasFactory<MembershipFactory> */
    use BelongsToOrganization, HasFactory, HasUuids;

    public const ROLE_OWNER = 'owner';

    public const ROLE_MEMBER = 'member';

    public const ROLE_VIEWER = 'viewer';

    protected $fillable = [
        'organization_id',
        'user_id',
        'role',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isOwner(): bool
    {
        return $this->role === self::ROLE_OWNER;
    }
}
