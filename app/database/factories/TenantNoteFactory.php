<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\TenantNote;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TenantNote>
 */
class TenantNoteFactory extends Factory
{
    protected $model = TenantNote::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'user_id' => User::factory(),
            'title' => fake()->sentence(3),
            'body' => fake()->paragraph(),
        ];
    }
}
