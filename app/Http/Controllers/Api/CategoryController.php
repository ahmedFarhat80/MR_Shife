<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\ProductResource;
use App\Models\InternalCategory;
use App\Models\Product;
use App\Services\ApiResponseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class CategoryController extends Controller
{
    protected $apiResponse;

    public function __construct(ApiResponseService $apiResponse)
    {
        $this->apiResponse = $apiResponse;
    }

    /**
     * Get categories for home page filter (text-based horizontal filter)
     * Used in: Home screen category filter
     */
    public function getHomeCategories(Request $request)
    {
        try {
            $cacheKey = 'home_categories_' . app()->getLocale();

            $categories = Cache::remember($cacheKey, 3600, function () {
                return InternalCategory::where('is_active', true)
                    ->where('show_in_home', true)
                    ->orderBy('sort_order')
                    ->take(6) // Limit to 6 categories for home
                    ->get(['id', 'name', 'slug'])
                    ->map(function ($category) {
                        return [
                            'id' => $category->id,
                            'name' => $category->name,
                            'slug' => $category->slug,
                        ];
                    });
            });

            // Add "Popular" as first item (special category)
            $homeCategories = collect([
                [
                    'id' => 0,
                    'name' => __('Popular'),
                    'slug' => 'popular',
                ]
            ])->merge($categories);

            return $this->apiResponse->success(
                __('categories.retrieved_successfully'),
                $homeCategories
            );

        } catch (\Exception $e) {
            return $this->apiResponse->error(__('categories.failed_to_retrieve'));
        }
    }

    /**
     * Get category chips for categories page (circular chips with icons and item count)
     * Used in: Categories screen filter chips
     */
    public function getCategoryChips(Request $request)
    {
        try {
            $cacheKey = 'category_chips_' . app()->getLocale();

            $categoryChips = Cache::remember($cacheKey, 3600, function () {
                return InternalCategory::where('is_active', true)
                    ->where('show_in_categories', true)
                    ->withCount(['products' => function ($query) {
                        $query->where('is_active', true);
                    }])
                    ->orderBy('sort_order')
                    ->get()
                    ->map(function ($category) {
                        return [
                            'id' => $category->id,
                            'name' => $category->name,
                            'icon' => $category->icon_url, // URL to category icon
                            'itemCount' => $category->products_count,
                            'isSelected' => false, // Client will handle selection
                        ];
                    });
            });

            return $this->apiResponse->success(
                __('categories.chips_retrieved_successfully'),
                $categoryChips
            );

        } catch (\Exception $e) {
            return $this->apiResponse->error(__('categories.failed_to_retrieve'));
        }
    }

    /**
     * Get all categories with hierarchy (for filters and navigation)
     */
    public function getCategories(Request $request)
    {
        try {
            $cacheKey = 'all_categories_' . app()->getLocale();

            $categories = Cache::remember($cacheKey, 3600, function () {
                return InternalCategory::where('is_active', true)
                    ->whereNull('parent_id') // Get parent categories only
                    ->with(['children' => function ($query) {
                        $query->where('is_active', true)
                            ->withCount(['products' => function ($q) {
                                $q->where('is_active', true);
                            }]);
                    }])
                    ->withCount(['products' => function ($query) {
                        $query->where('is_active', true);
                    }])
                    ->orderBy('sort_order')
                    ->get();
            });

            return $this->apiResponse->success(
                __('categories.retrieved_successfully'),
                CategoryResource::collection($categories)
            );

        } catch (\Exception $e) {
            return $this->apiResponse->error(__('categories.failed_to_retrieve'));
        }
    }

    /**
     * Get products by category
     */
    public function getCategoryProducts(Request $request, $categoryId)
    {
        try {
            $perPage = $request->get('per_page', 20);
            $sortBy = $request->get('sort_by', 'created_at'); // created_at, price, rating, name
            $sortOrder = $request->get('sort_order', 'desc'); // asc, desc

            $category = InternalCategory::findOrFail($categoryId);

            $query = Product::where('is_available', true)
                ->where('internal_category_id', $categoryId)
                ->with(['merchant', 'images', 'internalCategory', 'foodNationality']);

            // Apply sorting
            switch ($sortBy) {
                case 'price':
                    $query->orderBy('price', $sortOrder);
                    break;
                case 'rating':
                    $query->orderBy('average_rating', $sortOrder);
                    break;
                case 'name':
                    $query->orderBy('name', $sortOrder);
                    break;
                default:
                    $query->orderBy('created_at', $sortOrder);
            }

            $products = $query->paginate($perPage);

            return $this->apiResponse->success(
                __('categories.products_retrieved_successfully'),
                [
                    'category' => new CategoryResource($category),
                    'products' => ProductResource::collection($products->items()),
                    'pagination' => [
                        'current_page' => $products->currentPage(),
                        'last_page' => $products->lastPage(),
                        'per_page' => $products->perPage(),
                        'total' => $products->total(),
                        'has_more' => $products->hasMorePages(),
                    ]
                ]
            );

        } catch (\Exception $e) {
            return $this->apiResponse->error(__('categories.failed_to_retrieve_products'));
        }
    }

    /**
     * Get filter options for advanced filtering
     * Used in: Categories screen filters bottom sheet
     */
    public function getFilterOptions(Request $request)
    {
        try {
            $cacheKey = 'filter_options_' . app()->getLocale();

            $filterOptions = Cache::remember($cacheKey, 3600, function () {
                return [
                    [
                        'title' => __('Category'),
                        'type' => 'category',
                        'options' => InternalCategory::where('is_active', true)
                            ->whereNull('parent_id')
                            ->withCount(['products' => function ($query) {
                                $query->where('is_active', true);
                            }])
                            ->get()
                            ->map(function ($category) {
                                return [
                                    'id' => $category->id,
                                    'name' => $category->name,
                                    'count' => $category->products_count,
                                    'isSelected' => false,
                                ];
                            })
                    ],
                    [
                        'title' => __('Sub-Category'),
                        'type' => 'sub_category',
                        'options' => InternalCategory::where('is_active', true)
                            ->whereNotNull('parent_id')
                            ->withCount(['products' => function ($query) {
                                $query->where('is_active', true);
                            }])
                            ->get()
                            ->map(function ($category) {
                                return [
                                    'id' => $category->id,
                                    'name' => $category->name,
                                    'count' => $category->products_count,
                                    'isSelected' => false,
                                ];
                            })
                    ],
                    [
                        'title' => __('Price Range'),
                        'type' => 'price_range',
                        'options' => [
                            ['id' => 1, 'name' => __('Less than $50'), 'min' => 0, 'max' => 50, 'count' => 0, 'isSelected' => false],
                            ['id' => 2, 'name' => __('$50 - $100'), 'min' => 50, 'max' => 100, 'count' => 0, 'isSelected' => false],
                            ['id' => 3, 'name' => __('$100 - $250'), 'min' => 100, 'max' => 250, 'count' => 0, 'isSelected' => false],
                            ['id' => 4, 'name' => __('$250 - $500'), 'min' => 250, 'max' => 500, 'count' => 0, 'isSelected' => false],
                            ['id' => 5, 'name' => __('More than $500'), 'min' => 500, 'max' => null, 'count' => 0, 'isSelected' => false],
                        ]
                    ],
                    [
                        'title' => __('Features'),
                        'type' => 'features',
                        'options' => [
                            ['id' => 1, 'name' => __('Vegetarian'), 'key' => 'vegetarian', 'count' => 0, 'isSelected' => false],
                            ['id' => 2, 'name' => __('Gluten Free'), 'key' => 'gluten_free', 'count' => 0, 'isSelected' => false],
                            ['id' => 3, 'name' => __('Organic'), 'key' => 'organic', 'count' => 0, 'isSelected' => false],
                            ['id' => 4, 'name' => __('Vegan'), 'key' => 'vegan', 'count' => 0, 'isSelected' => false],
                        ]
                    ],
                    [
                        'title' => __('Rating'),
                        'type' => 'rating',
                        'options' => [
                            ['id' => 1, 'name' => __('5 Stars'), 'rating' => 5.0, 'count' => 0, 'isSelected' => false],
                            ['id' => 2, 'name' => __('4+ Stars'), 'rating' => 4.0, 'count' => 0, 'isSelected' => false],
                            ['id' => 3, 'name' => __('3+ Stars'), 'rating' => 3.0, 'count' => 0, 'isSelected' => false],
                        ]
                    ],
                    [
                        'title' => __('Preparation Time'),
                        'type' => 'prep_time',
                        'options' => [
                            ['id' => 1, 'name' => __('Less than 15 min'), 'max_time' => 15, 'count' => 0, 'isSelected' => false],
                            ['id' => 2, 'name' => __('15 - 30 min'), 'min_time' => 15, 'max_time' => 30, 'count' => 0, 'isSelected' => false],
                            ['id' => 3, 'name' => __('30 - 45 min'), 'min_time' => 30, 'max_time' => 45, 'count' => 0, 'isSelected' => false],
                            ['id' => 4, 'name' => __('More than 45 min'), 'min_time' => 45, 'max_time' => null, 'count' => 0, 'isSelected' => false],
                        ]
                    ]
                ];
            });

            return $this->apiResponse->success(
                __('categories.filter_options_retrieved_successfully'),
                $filterOptions
            );

        } catch (\Exception $e) {
            return $this->apiResponse->error(__('categories.failed_to_retrieve_filter_options'));
        }
    }
}
