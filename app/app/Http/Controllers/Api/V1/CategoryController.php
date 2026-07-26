<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class CategoryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Category::class);

        $categories = Category::query()
            ->orderBy('kind')
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'kind', 'is_system']);

        return response()->json(['data' => $categories]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', Category::class);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'slug' => ['nullable', 'string', 'max:120', 'alpha_dash'],
            'kind' => ['required', 'in:expense,income'],
        ]);

        $slug = $data['slug'] ?? str($data['name'])->slug()->toString();

        $category = Category::query()->create([
            'name' => $data['name'],
            'slug' => $slug !== '' ? $slug : 'categoria',
            'kind' => $data['kind'],
            'is_system' => false,
        ]);

        return response()->json(['data' => $category], 201);
    }

    public function destroy(Category $category): JsonResponse
    {
        $this->authorize('delete', $category);

        $category->delete();

        return response()->json(null, 204);
    }
}
