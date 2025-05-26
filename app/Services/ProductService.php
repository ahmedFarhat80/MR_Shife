<?php

namespace App\Services;

use App\DTO\ProductDTO;
use App\Models\Product;
use App\Repositories\ProductRepository;
use App\Repositories\CategoryRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class ProductService
{
    public function __construct(
        private ProductRepository $productRepository,
        private CategoryRepository $categoryRepository
    ) {}

    /**
     * Get products for merchant.
     *
     * @param int $merchantId
     * @param array $filters
     * @param bool $paginated
     * @param int $perPage
     * @return \Illuminate\Database\Eloquent\Collection|\Illuminate\Pagination\LengthAwarePaginator
     */
    public function getProducts(int $merchantId, array $filters = [], bool $paginated = false, int $perPage = 15)
    {
        if ($paginated) {
            return $this->productRepository->getPaginatedByServiceProvider($merchantId, $filters, $perPage);
        }

        return $this->productRepository->getByServiceProvider($merchantId, $filters);
    }

    /**
     * Create a new product.
     *
     * @param ProductDTO $productDTO
     * @return Product
     * @throws \Exception
     */
    public function createProduct(ProductDTO $productDTO): Product
    {
        // Validate category belongs to service provider
        $category = $this->categoryRepository->find($productDTO->categoryId);
                    if (!$category || $category->merchant_id !== $productDTO->merchantId) {
            throw new \Exception('Category does not belong to this merchant.');
        }

        // Check SKU uniqueness if provided
        if ($productDTO->sku && $this->productRepository->skuExists($productDTO->sku, $productDTO->merchantId)) {
            throw new \Exception('SKU already exists for this merchant.');
        }

        return $this->productRepository->create($productDTO->toArray());
    }

    /**
     * Update an existing product.
     *
     * @param int $productId
     * @param ProductDTO $productDTO
     * @return bool
     * @throws \Exception
     */
    public function updateProduct(int $productId, ProductDTO $productDTO): bool
    {
        $product = $this->productRepository->find($productId);
        if (!$product || $product->merchant_id !== $productDTO->merchantId) {
            throw new \Exception('Product not found or does not belong to this merchant.');
        }

        // Validate category belongs to merchant
        $category = $this->categoryRepository->find($productDTO->categoryId);
        if (!$category || $category->merchant_id !== $productDTO->merchantId) {
            throw new \Exception('Category does not belong to this merchant.');
        }

        // Check SKU uniqueness if provided
        if ($productDTO->sku && $this->productRepository->skuExists($productDTO->sku, $productDTO->merchantId, $productId)) {
            throw new \Exception('SKU already exists for this merchant.');
        }

        return $this->productRepository->update($productId, $productDTO->toArray());
    }

    /**
     * Delete a product.
     *
     * @param int $productId
     * @param int $merchantId
     * @return bool
     * @throws \Exception
     */
    public function deleteProduct(int $productId, int $merchantId): bool
    {
        $product = $this->productRepository->find($productId);
        if (!$product || $product->merchant_id !== $merchantId) {
            throw new \Exception('Product not found or does not belong to this merchant.');
        }

        return $this->productRepository->delete($productId);
    }

    /**
     * Get a single product.
     *
     * @param int $productId
     * @param int $merchantId
     * @return Product|null
     */
    public function getProduct(int $productId, int $merchantId): ?Product
    {
        $product = $this->productRepository->find($productId);
        if (!$product || $product->merchant_id !== $merchantId) {
            return null;
        }

        return $product;
    }

    /**
     * Update product availability.
     *
     * @param int $productId
     * @param int $merchantId
     * @param bool $isAvailable
     * @return bool
     * @throws \Exception
     */
    public function updateAvailability(int $productId, int $merchantId, bool $isAvailable): bool
    {
        $product = $this->productRepository->find($productId);
        if (!$product || $product->merchant_id !== $merchantId) {
            throw new \Exception('Product not found or does not belong to this merchant.');
        }

        return $this->productRepository->updateAvailability($productId, $isAvailable);
    }

    /**
     * Update product stock.
     *
     * @param int $productId
     * @param int $merchantId
     * @param int $quantity
     * @return bool
     * @throws \Exception
     */
    public function updateStock(int $productId, int $merchantId, int $quantity): bool
    {
        $product = $this->productRepository->find($productId);
        if (!$product || $product->merchant_id !== $merchantId) {
            throw new \Exception('Product not found or does not belong to this merchant.');
        }

        return $this->productRepository->updateStock($productId, $quantity);
    }

    /**
     * Bulk update product availability.
     *
     * @param array $productIds
     * @param int $merchantId
     * @param bool $isAvailable
     * @return int
     * @throws \Exception
     */
    public function bulkUpdateAvailability(array $productIds, int $merchantId, bool $isAvailable): int
    {
        // Verify all products belong to the merchant
        $products = $this->productRepository->findBy(['merchant_id' => $merchantId]);
        $validProductIds = $products->pluck('id')->toArray();

        $filteredProductIds = array_intersect($productIds, $validProductIds);

        if (empty($filteredProductIds)) {
            throw new \Exception('No valid products found for this merchant.');
        }

        return $this->productRepository->bulkUpdateAvailability($filteredProductIds, $isAvailable);
    }

    /**
     * Get featured products.
     *
     * @param int $merchantId
     * @return Collection
     */
    public function getFeaturedProducts(int $merchantId): Collection
    {
        return $this->productRepository->getFeaturedByServiceProvider($merchantId);
    }

    /**
     * Get low stock products.
     *
     * @param int $merchantId
     * @param int $threshold
     * @return Collection
     */
    public function getLowStockProducts(int $merchantId, int $threshold = 10): Collection
    {
        return $this->productRepository->getLowStockProducts($merchantId, $threshold);
    }

    /**
     * Get out of stock products.
     *
     * @param int $merchantId
     * @return Collection
     */
    public function getOutOfStockProducts(int $merchantId): Collection
    {
        return $this->productRepository->getOutOfStockProducts($merchantId);
    }

    /**
     * Get products by category.
     *
     * @param int $categoryId
     * @param int $merchantId
     * @return Collection
     * @throws \Exception
     */
    public function getProductsByCategory(int $categoryId, int $merchantId): Collection
    {
        $category = $this->categoryRepository->find($categoryId);
        if (!$category || $category->merchant_id !== $merchantId) {
            throw new \Exception('Category not found or does not belong to this merchant.');
        }

        return $this->productRepository->getByCategory($categoryId);
    }
}
