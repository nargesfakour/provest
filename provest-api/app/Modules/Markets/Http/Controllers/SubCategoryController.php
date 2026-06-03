<?php

declare(strict_types=1);

namespace App\Modules\Markets\Http\Controllers;

use App\Modules\Markets\Http\Requests\StoreSubCategoryRequest;
use App\Modules\Markets\Http\Requests\UpdateSubCategoryRequest;
use App\Modules\Markets\Http\Resources\SubCategoryResource;
use App\Modules\Markets\Services\CategoryServiceInterface;
use App\Modules\Markets\Services\SubCategoryServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;

class SubCategoryController extends Controller
{
    public function __construct(
        private readonly SubCategoryServiceInterface $subCategoryService,
        private readonly CategoryServiceInterface    $categoryService,
    ) {}

    public function index(Request $request, string $categoryUlid): AnonymousResourceCollection
    {
        $subCategories = $this->subCategoryService->listByCategory(
            categoryUlid: $categoryUlid,
            perPage:      (int) $request->input('per_page', 20),
        );

        return SubCategoryResource::collection($subCategories);
    }

    public function store(StoreSubCategoryRequest $request, string $categoryUlid): JsonResponse
    {
        $category = $this->categoryService->findByUlid($categoryUlid);
        $subCategory = $this->subCategoryService->create($request->toDTO($category->id));

        return response()->json(new SubCategoryResource($subCategory), 201);
    }

    public function update(string $ulid, UpdateSubCategoryRequest $request): JsonResponse
    {
        $subCategory = $this->subCategoryService->update($ulid, $request->toDTO());

        return response()->json(new SubCategoryResource($subCategory));
    }

    public function destroy(string $ulid): JsonResponse
    {
        $this->subCategoryService->delete($ulid);

        return response()->json(null, 204);
    }
}
