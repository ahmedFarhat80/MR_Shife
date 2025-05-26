<?php

namespace App\Repositories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class ProductRepository extends BaseRepository
{
    /**
     * Create a new repository instance.
     */
    public function __construct(Product $model)
    {
        parent::__construct($model);
    }

    /**
     * Get products by merchant with filters.
     *
     * @param int $merchantId
     * @param array $filters
     * @return Collection
     */
    public function getByServiceProvider(int $merchantId, array $filters = []): Collection
    {
        $query = $this->model->where('merchant_id', $merchantId)
            ->with(['category']);

        if (isset($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        if (isset($filters['is_available'])) {
            $query->where('is_available', $filters['is_available']);
        }

        if (isset($filters['is_featured'])) {
            $query->where('is_featured', $filters['is_featured']);
        }

        if (isset($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name->en', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('name->ar', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('sku', 'like', '%' . $filters['search'] . '%');
            });
        }

        return $query->orderBy('sort_order')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get paginated products by merchant.
     *
     * @param int $merchantId
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getPaginatedByServiceProvider(int $merchantId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->where('merchant_id', $merchantId)
            ->with(['category']);

        if (isset($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        if (isset($filters['is_available'])) {
            $query->where('is_available', $filters['is_available']);
        }

        if (isset($filters['is_featured'])) {
            $query->where('is_featured', $filters['is_featured']);
        }

        if (isset($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name->en', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('name->ar', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('sku', 'like', '%' . $filters['search'] . '%');
            });
        }

        return $query->orderBy('sort_order')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Get featured products by merchant.
     *
     * @param int $merchantId
     * @return Collection
     */
    public function getFeaturedByServiceProvider(int $merchantId): Collection
    {
        return $this->model->where('merchant_id', $merchantId)
            ->where('is_featured', true)
            ->where('is_available', true)
            ->with(['category'])
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * Get products by category.
     *
     * @param int $categoryId
     * @return Collection
     */
    public function getByCategory(int $categoryId): Collection
    {
        return $this->model->where('category_id', $categoryId)
            ->where('is_available', true)
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * Check if SKU exists for merchant.
     *
     * @param string $sku
     * @param int $merchantId
     * @param int|null $excludeId
     * @return bool
     */
    public function skuExists(string $sku, int $merchantId, ?int $excludeId = null): bool
    {
        $query = $this->model->where('merchant_id', $merchantId)
            ->where('sku', $sku);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    /**
     * Update product availability.
     *
     * @param int $productId
     * @param bool $isAvailable
     * @return bool
     */
    public function updateAvailability(int $productId, bool $isAvailable): bool
    {
        return $this->update($productId, ['is_available' => $isAvailable]);
    }

    /**
     * Update product stock.
     *
     * @param int $productId
     * @param int $quantity
     * @return bool
     */
    public function updateStock(int $productId, int $quantity): bool
    {
        return $this->update($productId, ['stock_quantity' => $quantity]);
    }

    /**
     * Bulk update product availability.
     *
     * @param array $productIds
     * @param bool $isAvailable
     * @return int
     */
    public function bulkUpdateAvailability(array $productIds, bool $isAvailable): int
    {
        return $this->model->whereIn('id', $productIds)
            ->update(['is_available' => $isAvailable]);
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
        return $this->model->where('merchant_id', $merchantId)
            ->where('track_stock', true)
            ->where('stock_quantity', '<=', $threshold)
            ->where('stock_quantity', '>', 0)
            ->with(['category'])
            ->orderBy('stock_quantity')
            ->get();
    }

    /**
     * Get out of stock products.
     *
     * @param int $merchantId
     * @return Collection
     */
    public function getOutOfStockProducts(int $merchantId): Collection
    {
        return $this->model->where('merchant_id', $merchantId)
            ->where(function ($query) {
                $query->where('stock_quantity', 0)
                    ->orWhere('is_available', false);
            })
            ->with(['category'])
            ->orderBy('name->en')
            ->get();
    }
}
