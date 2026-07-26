<?php

namespace App\Services\Finance;

use App\Models\Category;
use App\Models\Organization;
use Illuminate\Support\Facades\DB;

final class SeedsDefaultCategories
{
    /**
     * @return list<array{name: string, slug: string, kind: string}>
     */
    public static function defaults(): array
    {
        return [
            ['name' => 'Moradia', 'slug' => 'moradia', 'kind' => Category::KIND_EXPENSE],
            ['name' => 'Alimentação', 'slug' => 'alimentacao', 'kind' => Category::KIND_EXPENSE],
            ['name' => 'Transporte', 'slug' => 'transporte', 'kind' => Category::KIND_EXPENSE],
            ['name' => 'Saúde', 'slug' => 'saude', 'kind' => Category::KIND_EXPENSE],
            ['name' => 'Lazer', 'slug' => 'lazer', 'kind' => Category::KIND_EXPENSE],
            ['name' => 'Educação', 'slug' => 'educacao', 'kind' => Category::KIND_EXPENSE],
            ['name' => 'Salário', 'slug' => 'salario', 'kind' => Category::KIND_INCOME],
            ['name' => 'Outros', 'slug' => 'outros', 'kind' => Category::KIND_EXPENSE],
        ];
    }

    public function handle(Organization $organization): void
    {
        DB::transaction(function () use ($organization): void {
            foreach (self::defaults() as $row) {
                Category::query()->withoutGlobalScopes()->updateOrCreate(
                    [
                        'organization_id' => $organization->id,
                        'slug' => $row['slug'],
                    ],
                    [
                        'name' => $row['name'],
                        'kind' => $row['kind'],
                        'is_system' => true,
                    ],
                );
            }
        });
    }
}
