<?php

namespace Database\Factories;

use App\Models\Membership;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Membership>
 */
class MembershipFactory extends Factory
{
    protected $model = Membership::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'user_id' => User::factory(),
            'role' => Membership::ROLE_MEMBER,
        ];
    }

    public function owner(): static
    {
        return $this->state(fn () => ['role' => Membership::ROLE_OWNER]);
    }

    public function viewer(): static
    {
        return $this->state(fn () => ['role' => Membership::ROLE_VIEWER]);
    }
}
