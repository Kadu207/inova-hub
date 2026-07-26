<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Organization;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Transaction>
 */
class TransactionFactory extends Factory
{
    protected $model = Transaction::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'category_id' => Category::factory(),
            'user_id' => User::factory(),
            'amount_cents' => fake()->numberBetween(100, 50_000),
            'type' => Transaction::TYPE_EXPENSE,
            'currency' => 'BRL',
            'source' => Transaction::SOURCE_MANUAL,
            'description' => fake()->sentence(3),
            'occurred_at' => fake()->dateTimeBetween('-30 days', 'now'),
        ];
    }

    public function income(): static
    {
        return $this->state(fn () => ['type' => Transaction::TYPE_INCOME]);
    }
}
