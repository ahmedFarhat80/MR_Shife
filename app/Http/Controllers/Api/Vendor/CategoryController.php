<?php

namespace App\Http\Controllers\Api\Vendor;

use App\DTO\CategoryDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\CreateCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Services\ApiResponseService;
use App\Services\CategoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function __construct(
        private ApiResponseService $apiResponse,
        private CategoryService $categoryService
    ) {}

    /**
     * Display a listing of categories.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $serviceProviderId = $request->user()->id;
            $activeOnly = $request->boolean('active_only', false);
            $withProducts = $request->boolean('with_products', false);

            if ($withProducts) {
                $categories = $this->categoryService->getCategoriesWithProducts($serviceProviderId);
            } else {
                $categories = $this->categoryService->getCategories($serviceProviderId, $activeOnly);
            }

            return $this->apiResponse->success(
                'Categories retrieved successfully',
                CategoryResource::collection($categories)
            );
        } catch (\Exception $e) {
            return $this->apiResponse->error('Failed to retrieve categories: ' . $e->getMessage());
        }
    }

    /**
     * Store a newly created category.
     */
    public function store(CreateCategoryRequest $request): JsonResponse
    {
        try {
            $serviceProviderId = $request->user()->id;
            $categoryDTO = CategoryDTO::fromRequest($request->validated(), $serviceProviderId);
            
            $category = $this->categoryService->createCategory($categoryDTO);
            
            return $this->apiResponse->success(
                'Category created successfully',
                new CategoryResource($category),
                [],
                201
            );
        } catch (\Exception $e) {
            return $this->apiResponse->error('Failed to create category: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified category.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        try {
            $serviceProviderId = $request->user()->id;
            $category = $this->categoryService->getCategory($id, $serviceProviderId);
            
            if (!$category) {
                return $this->apiResponse->error('Category not found', null, 404);
            }

            return $this->apiResponse->success(
                'Category retrieved successfully',
                new CategoryResource($category->load('products'))
            );
        } catch (\Exception $e) {
            return $this->apiResponse->error('Failed to retrieve category: ' . $e->getMessage());
        }
    }

    /**
     * Update the specified category.
     */
    public function update(CreateCategoryRequest $request, int $id): JsonResponse
    {
        try {
            $serviceProviderId = $request->user()->id;
            $categoryDTO = CategoryDTO::fromRequest($request->validated(), $serviceProviderId);
            
            $updated = $this->categoryService->updateCategory($id, $categoryDTO);
            
            if (!$updated) {
                return $this->apiResponse->error('Failed to update category');
            }

            $category = $this->categoryService->getCategory($id, $serviceProviderId);
            
            return $this->apiResponse->success(
                'Category updated successfully',
                new CategoryResource($category)
            );
        } catch (\Exception $e) {
            return $this->apiResponse->error('Failed to update category: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified category.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        try {
            $serviceProviderId = $request->user()->id;
            $deleted = $this->categoryService->deleteCategory($id, $serviceProviderId);
            
            if (!$deleted) {
                return $this->apiResponse->error('Failed to delete category');
            }

            return $this->apiResponse->success('Category deleted successfully');
        } catch (\Exception $e) {
            return $this->apiResponse->error('Failed to delete category: ' . $e->getMessage());
        }
    }

    /**
     * Update category status.
     */
    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'is_active' => 'required|boolean'
        ]);

        try {
            $serviceProviderId = $request->user()->id;
            $isActive = $request->boolean('is_active');
            
            $updated = $this->categoryService->updateStatus($id, $serviceProviderId, $isActive);
            
            if (!$updated) {
                return $this->apiResponse->error('Failed to update category status');
            }

            return $this->apiResponse->success(
                'Category status updated successfully',
                ['is_active' => $isActive]
            );
        } catch (\Exception $e) {
            return $this->apiResponse->error('Failed to update status: ' . $e->getMessage());
        }
    }

    /**
     * Reorder categories.
     */
    public function reorder(Request $request): JsonResponse
    {
        $request->validate([
            'categories' => 'required|array|min:1',
            'categories.*.id' => 'required|integer|exists:categories,id',
            'categories.*.sort_order' => 'required|integer|min:0',
        ]);

        try {
            $serviceProviderId = $request->user()->id;
            $categoryOrders = $request->input('categories');
            
            $updated = $this->categoryService->reorderCategories($categoryOrders, $serviceProviderId);
            
            if (!$updated) {
                return $this->apiResponse->error('Failed to reorder categories');
            }

            return $this->apiResponse->success('Categories reordered successfully');
        } catch (\Exception $e) {
            return $this->apiResponse->error('Failed to reorder categories: ' . $e->getMessage());
        }
    }
}
