<?php

namespace App\Repositories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Collection;

class CategoryRepository extends BaseRepository
{
    /**
     * Create a new repository instance.
     */
    public function __construct(Category $model)
    {
        parent::__construct($model);
    }

    /**
     * Get categories by merchant.
     *
     * @param int $merchantId
     * @param bool $activeOnly
     * @return Collection
     */
    public function getByMerchant(int $merchantId, bool $activeOnly = false): Collection
    {
        $query = $this->model->where('merchant_id', $merchantId)
            ->orderBy('sort_order')
            ->orderBy('created_at', 'desc');

        if ($activeOnly) {
            $query->where('is_active', true);
        }

        return $query->get();
    }

    /**
     * Get active categories by merchant.
     *
     * @param int $merchantId
     * @return Collection
     */
    public function getActiveByMerchant(int $merchantId): Collection
    {
        return $this->model->where('merchant_id', $merchantId)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get active categories with products.
     *
     * @param int $merchantId
     * @return Collection
     */
    public function getActiveWithProducts(int $merchantId): Collection
    {
        return $this->model->where('merchant_id', $merchantId)
            ->where('is_active', true)
            ->whereHas('products', function ($query) {
                $query->where('is_available', true);
            })
            ->with(['products' => function ($query) {
                $query->where('is_available', true)
                    ->orderBy('sort_order')
                    ->orderBy('created_at', 'desc');
            }])
            ->orderBy('sort_order')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Update category status.
     *
     * @param int $categoryId
     * @param bool $isActive
     * @return bool
     */
    public function updateStatus(int $categoryId, bool $isActive): bool
    {
        return $this->update($categoryId, ['is_active' => $isActive]);
    }

    /**
     * Reorder categories.
     *
     * @param array $categoryOrders Array of ['id' => categoryId, 'order' => sortOrder]
     * @return bool
     */
    public function reorder(array $categoryOrders): bool
    {
        foreach ($categoryOrders as $order) {
            $this->model->where('id', $order['id'])
                ->update(['sort_order' => $order['order']]);
        }

        return true;
    }

    /**
     * Get categories with product count.
     *
     * @param int $merchantId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getWithProductCount(int $merchantId): Collection
    {
        return $this->model->where('merchant_id', $merchantId)
            ->withCount(['products', 'activeProducts'])
            ->ordered()
            ->get();
    }

    /**
     * Check if category has products.
     *
     * @param int $categoryId
     * @return bool
     */
    public function hasProducts(int $categoryId): bool
    {
        return $this->model->find($categoryId)?->products()->exists() ?? false;
    }
}
