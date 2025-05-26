<?php

namespace App\Services;

use App\DTO\CategoryDTO;
use App\Models\Category;
use App\Repositories\CategoryRepository;
use Illuminate\Database\Eloquent\Collection;

class CategoryService
{
    public function __construct(
        private CategoryRepository $categoryRepository
    ) {}

    /**
     * Get all categories for a merchant.
     *
     * @param int $merchantId
     * @param bool $activeOnly
     * @return Collection
     */
    public function getCategories(int $merchantId, bool $activeOnly = false): Collection
    {
        if ($activeOnly) {
            return $this->categoryRepository->getActiveByMerchant($merchantId);
        }

        return $this->categoryRepository->getByMerchant($merchantId);
    }

    /**
     * Get categories with products.
     *
     * @param int $merchantId
     * @return Collection
     */
    public function getCategoriesWithProducts(int $merchantId): Collection
    {
        return $this->categoryRepository->getActiveWithProducts($merchantId);
    }

    /**
     * Create a new category.
     *
     * @param CategoryDTO $categoryDTO
     * @return Category
     */
    public function createCategory(CategoryDTO $categoryDTO): Category
    {
        return $this->categoryRepository->create($categoryDTO->toArray());
    }

    /**
     * Update an existing category.
     *
     * @param int $categoryId
     * @param CategoryDTO $categoryDTO
     * @return bool
     * @throws \Exception
     */
    public function updateCategory(int $categoryId, CategoryDTO $categoryDTO): bool
    {
        $category = $this->categoryRepository->find($categoryId);
        if (!$category || $category->merchant_id !== $categoryDTO->merchantId) {
            throw new \Exception('Category not found or does not belong to this merchant.');
        }

        return $this->categoryRepository->update($categoryId, $categoryDTO->toArray());
    }

    /**
     * Delete a category.
     *
     * @param int $categoryId
     * @param int $merchantId
     * @return bool
     * @throws \Exception
     */
    public function deleteCategory(int $categoryId, int $merchantId): bool
    {
        $category = $this->categoryRepository->find($categoryId);
        if (!$category || $category->merchant_id !== $merchantId) {
            throw new \Exception('Category not found or does not belong to this merchant.');
        }

        // Check if category has products
        if ($category->products()->exists()) {
            throw new \Exception('Cannot delete category with products.');
        }

        return $this->categoryRepository->delete($categoryId);
    }

    /**
     * Get a single category.
     *
     * @param int $categoryId
     * @param int $merchantId
     * @return Category|null
     */
    public function getCategory(int $categoryId, int $merchantId): ?Category
    {
        $category = $this->categoryRepository->find($categoryId);
        if (!$category || $category->merchant_id !== $merchantId) {
            return null;
        }

        return $category;
    }

    /**
     * Update category status.
     *
     * @param int $categoryId
     * @param int $merchantId
     * @param bool $isActive
     * @return bool
     * @throws \Exception
     */
    public function updateStatus(int $categoryId, int $merchantId, bool $isActive): bool
    {
        $category = $this->categoryRepository->find($categoryId);
        if (!$category || $category->merchant_id !== $merchantId) {
            throw new \Exception('Category not found or does not belong to this merchant.');
        }

        return $this->categoryRepository->updateStatus($categoryId, $isActive);
    }

    /**
     * Reorder categories.
     *
     * @param array $categoryOrders Array of ['id' => categoryId, 'order' => sortOrder]
     * @param int $merchantId
     * @return bool
     * @throws \Exception
     */
    public function reorderCategories(array $categoryOrders, int $merchantId): bool
    {
        // Verify all categories belong to the merchant
        $categoryIds = array_column($categoryOrders, 'id');
        $categories = $this->categoryRepository->findBy(['merchant_id' => $merchantId]);
        $validCategoryIds = $categories->pluck('id')->toArray();

        foreach ($categoryIds as $categoryId) {
            if (!in_array($categoryId, $validCategoryIds)) {
                throw new \Exception("Category {$categoryId} does not belong to this merchant.");
            }
        }

        return $this->categoryRepository->reorder($categoryOrders);
    }
}
