<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    protected $model = Category::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'organization_id' => Organization::factory(),
            'name' => Str::title($name),
            'slug' => Str::slug($name),
            'kind' => Category::KIND_EXPENSE,
            'is_system' => false,
        ];
    }

    public function income(): static
    {
        return $this->state(fn () => ['kind' => Category::KIND_INCOME]);
    }

    public function system(): static
    {
        return $this->state(fn () => ['is_system' => true]);
    }
}
