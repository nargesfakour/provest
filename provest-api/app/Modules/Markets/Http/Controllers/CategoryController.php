<?php

declare(strict_types=1);

namespace App\Modules\Markets\Http\Controllers;

use App\Modules\Markets\Http\Requests\StoreCategoryRequest;
use App\Modules\Markets\Http\Requests\UpdateCategoryRequest;
use App\Modules\Markets\Http\Resources\CategoryResource;
use App\Modules\Markets\Services\CategoryServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;

class CategoryController extends Controller
{
    public function __construct(
        private readonly CategoryServiceInterface $categoryService,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $categories = $this->categoryService->list(
            perPage: (int) $request->input('per_page', 20),
        );

        return CategoryResource::collection($categories);
    }

    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $category = $this->categoryService->create($request->toDTO());

        return response()->json(new CategoryResource($category), 201);
    }

    public function show(string $ulid): JsonResponse
    {
        $category = $this->categoryService->findByUlid($ulid);

        return response()->json(new CategoryResource($category));
    }

    public function update(string $ulid, UpdateCategoryRequest $request): JsonResponse
    {
        $category = $this->categoryService->update($ulid, $request->toDTO());

        return response()->json(new CategoryResource($category));
    }

    public function destroy(string $ulid): JsonResponse
    {
        $this->categoryService->delete($ulid);

        return response()->json(null, 204);
    }

    public function publicIndex(): AnonymousResourceCollection
    {
        return CategoryResource::collection($this->categoryService->listActive());
    }
}
