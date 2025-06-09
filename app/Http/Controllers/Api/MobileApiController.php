<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\HomeScreenResource;
use App\Http\Resources\MerchantListResource;
use App\Http\Resources\MerchantDetailResource;
use App\Http\Resources\ProductListResource;
use App\Http\Resources\ProductDetailResource;
use App\Http\Resources\InternalCategoryResource;
use App\Http\Requests\ProductListRequest;
use App\Models\Merchant;
use App\Models\Product;
use App\Models\InternalCategory;
use App\Models\FoodNationality;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class MobileApiController extends Controller
{
    /**
     * Get home screen data.
     */
    public function homeScreen(Request $request): JsonResponse
    {
        try {
            // Get featured merchants
            $featuredMerchants = Merchant::where('status', 'active')
                ->where('is_featured', true)
                ->with(['products' => function($query) {
                    $query->where('is_available', true)->limit(3);
                }])
                ->limit(6)
                ->get();

            // Get popular products
            $popularProducts = Product::where('is_available', true)
                ->where('is_popular', true)
                ->with(['merchant', 'internalCategory', 'foodNationality'])
                ->limit(8)
                ->get();

            // Get nearby merchants (mock data for now)
            $nearbyMerchants = Merchant::where('status', 'active')
                ->with(['products' => function($query) {
                    $query->where('is_available', true)->limit(3);
                }])
                ->limit(6)
                ->get();

            // Get trending products
            $trendingProducts = Product::where('is_available', true)
                ->where('is_featured', true)
                ->with(['merchant', 'internalCategory', 'foodNationality'])
                ->limit(6)
                ->get();

            // Create home screen data object
            $homeData = (object) [
                'featured_merchants' => $featuredMerchants,
                'popular_products' => $popularProducts,
                'nearby_merchants' => $nearbyMerchants,
                'trending_products' => $trendingProducts,
                'recommended_products' => collect(),
                'favorite_merchants' => collect(),
                'previous_orders' => collect(),
                'user_address' => null,
            ];

            return response()->json([
                'success' => true,
                'message' => __('api.home_screen_loaded_successfully'),
                'data' => new HomeScreenResource($homeData),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('api.error_loading_home_screen'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get merchants list with filtering and pagination.
     */
    public function merchants(Request $request): JsonResponse
    {
        try {
            $query = Merchant::where('status', 'active')
                ->with(['products' => function($q) {
                    $q->where('is_available', true)->limit(3);
                }]);

            // Apply filters
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(name, '$.en')) LIKE ?", ["%{$search}%"])
                      ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(name, '$.ar')) LIKE ?", ["%{$search}%"])
                      ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(business_name, '$.en')) LIKE ?", ["%{$search}%"])
                      ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(business_name, '$.ar')) LIKE ?", ["%{$search}%"]);
                });
            }

            if ($request->filled('business_type')) {
                $query->where('business_type', $request->business_type);
            }

            if ($request->filled('is_featured')) {
                $query->where('is_featured', $request->boolean('is_featured'));
            }

            if ($request->filled('delivery_fee_max')) {
                $query->where('delivery_fee', '<=', $request->delivery_fee_max);
            }

            if ($request->filled('minimum_order_max')) {
                $query->where('minimum_order', '<=', $request->minimum_order_max);
            }

            // Location-based filtering
            if ($request->filled(['user_lat', 'user_lng', 'radius'])) {
                $userLat = $request->user_lat;
                $userLng = $request->user_lng;
                $radius = $request->radius; // in kilometers

                $query->whereRaw(
                    "(6371 * acos(cos(radians(?)) * cos(radians(location_latitude)) * cos(radians(location_longitude) - radians(?)) + sin(radians(?)) * sin(radians(location_latitude)))) <= ?",
                    [$userLat, $userLng, $userLat, $radius]
                );
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');

            switch ($sortBy) {
                case 'rating':
                    $query->orderBy('average_rating', $sortOrder);
                    break;
                case 'delivery_fee':
                    $query->orderBy('delivery_fee', $sortOrder);
                    break;
                case 'minimum_order':
                    $query->orderBy('minimum_order', $sortOrder);
                    break;
                case 'distance':
                    if ($request->filled(['user_lat', 'user_lng'])) {
                        $userLat = $request->user_lat;
                        $userLng = $request->user_lng;
                        $query->orderByRaw(
                            "(6371 * acos(cos(radians(?)) * cos(radians(location_latitude)) * cos(radians(location_longitude) - radians(?)) + sin(radians(?)) * sin(radians(location_latitude)))) {$sortOrder}",
                            [$userLat, $userLng, $userLat]
                        );
                    }
                    break;
                default:
                    $query->orderBy($sortBy, $sortOrder);
            }

            // Pagination
            $perPage = min($request->get('per_page', 15), 50);
            $merchants = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'message' => __('api.merchants_loaded_successfully'),
                'data' => MerchantListResource::collection($merchants),
                'pagination' => [
                    'current_page' => $merchants->currentPage(),
                    'last_page' => $merchants->lastPage(),
                    'per_page' => $merchants->perPage(),
                    'total' => $merchants->total(),
                    'has_more_pages' => $merchants->hasMorePages(),
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('api.error_loading_merchants'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get merchant details.
     */
    public function merchantDetails(Request $request, $merchantId): JsonResponse
    {
        try {
            $merchant = Merchant::where('id', $merchantId)
                ->where('status', 'active')
                ->with([
                    'internalCategories' => function($query) {
                        $query->where('is_active', true)
                              ->withCount(['products' => function($q) {
                                  $q->where('is_available', true);
                              }]);
                    },
                    'featuredProducts' => function($query) {
                        $query->where('is_available', true)
                              ->where('is_featured', true)
                              ->limit(6);
                    },
                    'popularProducts' => function($query) {
                        $query->where('is_available', true)
                              ->where('is_popular', true)
                              ->limit(6);
                    }
                ])
                ->first();

            if (!$merchant) {
                return response()->json([
                    'success' => false,
                    'message' => __('api.merchant_not_found'),
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => __('api.merchant_details_loaded_successfully'),
                'data' => new MerchantDetailResource($merchant),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('api.error_loading_merchant_details'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get products by merchant with filtering.
     */
    public function merchantProducts(Request $request, $merchantId): JsonResponse
    {
        try {
            $merchant = Merchant::where('id', $merchantId)
                ->where('status', 'active')
                ->first();

            if (!$merchant) {
                return response()->json([
                    'success' => false,
                    'message' => __('api.merchant_not_found'),
                ], 404);
            }

            $query = Product::where('merchant_id', $merchantId)
                ->where('is_available', true)
                ->with(['merchant', 'internalCategory', 'foodNationality']);

            // Apply filters
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(name, '$.en')) LIKE ?", ["%{$search}%"])
                      ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(name, '$.ar')) LIKE ?", ["%{$search}%"])
                      ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(description, '$.en')) LIKE ?", ["%{$search}%"])
                      ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(description, '$.ar')) LIKE ?", ["%{$search}%"]);
                });
            }

            if ($request->filled('category_id')) {
                $query->where('internal_category_id', $request->category_id);
            }

            if ($request->filled('food_nationality_id')) {
                $query->where('food_nationality_id', $request->food_nationality_id);
            }

            if ($request->filled('is_vegetarian')) {
                $query->where('is_vegetarian', $request->boolean('is_vegetarian'));
            }

            if ($request->filled('is_spicy')) {
                $query->where('is_spicy', $request->boolean('is_spicy'));
            }

            if ($request->filled('has_discount')) {
                if ($request->boolean('has_discount')) {
                    $query->where('discount_percentage', '>', 0);
                } else {
                    $query->where(function($q) {
                        $q->whereNull('discount_percentage')
                          ->orWhere('discount_percentage', 0);
                    });
                }
            }

            if ($request->filled('price_min')) {
                $query->whereRaw('(base_price - (base_price * COALESCE(discount_percentage, 0) / 100)) >= ?', [$request->price_min]);
            }

            if ($request->filled('price_max')) {
                $query->whereRaw('(base_price - (base_price * COALESCE(discount_percentage, 0) / 100)) <= ?', [$request->price_max]);
            }

            if ($request->filled('preparation_time_max')) {
                $query->where('preparation_time', '<=', $request->preparation_time_max);
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');

            switch ($sortBy) {
                case 'price':
                    $query->orderByRaw('(base_price - (base_price * COALESCE(discount_percentage, 0) / 100)) ' . $sortOrder);
                    break;
                case 'rating':
                    $query->orderBy('average_rating', $sortOrder);
                    break;
                case 'popularity':
                    $query->orderBy('is_popular', 'desc')
                          ->orderBy('total_orders', 'desc');
                    break;
                case 'preparation_time':
                    $query->orderBy('preparation_time', $sortOrder);
                    break;
                default:
                    $query->orderBy($sortBy, $sortOrder);
            }

            // Pagination
            $perPage = min($request->get('per_page', 20), 50);
            $products = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'message' => __('api.products_loaded_successfully'),
                'data' => ProductListResource::collection($products),
                'merchant' => [
                    'id' => $merchant->id,
                    'name' => $merchant->getTranslation('business_name', $request->header('X-Language', 'en')),
                ],
                'pagination' => [
                    'current_page' => $products->currentPage(),
                    'last_page' => $products->lastPage(),
                    'per_page' => $products->perPage(),
                    'total' => $products->total(),
                    'has_more_pages' => $products->hasMorePages(),
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('api.error_loading_products'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get product details.
     */
    public function productDetails(Request $request, $productId): JsonResponse
    {
        try {
            $product = Product::where('id', $productId)
                ->where('is_available', true)
                ->with([
                    'merchant',
                    'internalCategory',
                    'foodNationality',
                    'relatedProducts' => function($query) {
                        $query->where('is_available', true)->limit(6);
                    }
                ])
                ->first();

            if (!$product) {
                return response()->json([
                    'success' => false,
                    'message' => __('api.product_not_found'),
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => __('api.product_details_loaded_successfully'),
                'data' => new ProductDetailResource($product),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('api.error_loading_product_details'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Search products across all merchants.
     * @deprecated Use SearchController::search instead for better functionality
     */
    public function searchProducts(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'query' => 'required|string|min:2|max:100',
                'per_page' => 'integer|min:1|max:50',
            ]);

            $searchQuery = $request->query;

            $query = Product::where('is_available', true)
                ->with(['merchant', 'internalCategory', 'foodNationality'])
                ->where(function($q) use ($searchQuery) {
                    $q->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(name, '$.en')) LIKE ?", ["%{$searchQuery}%"])
                      ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(name, '$.ar')) LIKE ?", ["%{$searchQuery}%"])
                      ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(description, '$.en')) LIKE ?", ["%{$searchQuery}%"])
                      ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(description, '$.ar')) LIKE ?", ["%{$searchQuery}%"]);
                });

            // Apply additional filters
            if ($request->filled('category_id')) {
                $query->where('internal_category_id', $request->category_id);
            }

            if ($request->filled('food_nationality_id')) {
                $query->where('food_nationality_id', $request->food_nationality_id);
            }

            if ($request->filled('merchant_id')) {
                $query->where('merchant_id', $request->merchant_id);
            }

            if ($request->filled('price_min')) {
                $query->whereRaw('(base_price - (base_price * COALESCE(discount_percentage, 0) / 100)) >= ?', [$request->price_min]);
            }

            if ($request->filled('price_max')) {
                $query->whereRaw('(base_price - (base_price * COALESCE(discount_percentage, 0) / 100)) <= ?', [$request->price_max]);
            }

            // Sorting by relevance (simplified)
            $query->orderByRaw("
                CASE
                    WHEN JSON_UNQUOTE(JSON_EXTRACT(name, '$.en')) LIKE ? THEN 1
                    WHEN JSON_UNQUOTE(JSON_EXTRACT(name, '$.ar')) LIKE ? THEN 1
                    ELSE 2
                END, is_featured DESC, average_rating DESC
            ", ["%{$searchQuery}%", "%{$searchQuery}%"]);

            $perPage = min($request->get('per_page', 20), 50);
            $products = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'message' => __('api.search_results_loaded_successfully'),
                'data' => ProductListResource::collection($products),
                'search_info' => [
                    'query' => $searchQuery,
                    'total_results' => $products->total(),
                    'page' => $products->currentPage(),
                ],
                'pagination' => [
                    'current_page' => $products->currentPage(),
                    'last_page' => $products->lastPage(),
                    'per_page' => $products->perPage(),
                    'total' => $products->total(),
                    'has_more_pages' => $products->hasMorePages(),
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('api.error_searching_products'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get categories for a specific merchant.
     */
    public function merchantCategories(Request $request, $merchantId): JsonResponse
    {
        try {
            $merchant = Merchant::where('id', $merchantId)
                ->where('status', 'active')
                ->first();

            if (!$merchant) {
                return response()->json([
                    'success' => false,
                    'message' => __('api.merchant_not_found'),
                ], 404);
            }

            $categories = InternalCategory::where('merchant_id', $merchantId)
                ->where('is_active', true)
                ->withCount(['products' => function($query) {
                    $query->where('is_available', true);
                }])
                ->with(['products' => function($query) {
                    $query->where('is_available', true)
                          ->where('is_featured', true)
                          ->limit(3);
                }])
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get();

            return response()->json([
                'success' => true,
                'message' => __('api.categories_loaded_successfully'),
                'data' => InternalCategoryResource::collection($categories),
                'merchant' => [
                    'id' => $merchant->id,
                    'name' => $merchant->getTranslation('business_name', $request->header('X-Language', 'en')),
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('api.error_loading_categories'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get all food nationalities.
     */
    public function foodNationalities(Request $request): JsonResponse
    {
        try {
            $nationalities = FoodNationality::where('is_active', true)
                ->withCount(['products' => function($query) {
                    $query->where('is_available', true);
                }])
                ->orderBy('name')
                ->get();

            $language = $request->header('X-Language', 'en');

            $data = $nationalities->map(function($nationality) use ($language) {
                return [
                    'id' => $nationality->id,
                    'name' => $nationality->getTranslation('name', $language),
                    'flag' => $nationality->flag,
                    'products_count' => $nationality->products_count,
                    'image' => $nationality->image ?? 'https://images.unsplash.com/photo-1504674900247-0877df9cc836?w=300&h=200&fit=crop',
                ];
            });

            return response()->json([
                'success' => true,
                'message' => __('api.food_nationalities_loaded_successfully'),
                'data' => $data,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('api.error_loading_food_nationalities'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get featured products across all merchants.
     */
    public function featuredProducts(Request $request): JsonResponse
    {
        try {
            $query = Product::where('is_available', true)
                ->where('is_featured', true)
                ->with(['merchant', 'internalCategory', 'foodNationality']);

            // Apply filters
            if ($request->filled('category_id')) {
                $query->where('internal_category_id', $request->category_id);
            }

            if ($request->filled('food_nationality_id')) {
                $query->where('food_nationality_id', $request->food_nationality_id);
            }

            if ($request->filled('merchant_id')) {
                $query->where('merchant_id', $request->merchant_id);
            }

            $query->orderBy('created_at', 'desc');

            $perPage = min($request->get('per_page', 20), 50);
            $products = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'message' => __('api.featured_products_loaded_successfully'),
                'data' => ProductListResource::collection($products),
                'pagination' => [
                    'current_page' => $products->currentPage(),
                    'last_page' => $products->lastPage(),
                    'per_page' => $products->perPage(),
                    'total' => $products->total(),
                    'has_more_pages' => $products->hasMorePages(),
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('api.error_loading_featured_products'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get popular products across all merchants.
     */
    public function popularProducts(Request $request): JsonResponse
    {
        try {
            $query = Product::where('is_available', true)
                ->where('is_popular', true)
                ->with(['merchant', 'internalCategory', 'foodNationality']);

            // Apply filters
            if ($request->filled('category_id')) {
                $query->where('internal_category_id', $request->category_id);
            }

            if ($request->filled('food_nationality_id')) {
                $query->where('food_nationality_id', $request->food_nationality_id);
            }

            if ($request->filled('merchant_id')) {
                $query->where('merchant_id', $request->merchant_id);
            }

            $query->orderBy('total_orders', 'desc')
                  ->orderBy('average_rating', 'desc');

            $perPage = min($request->get('per_page', 20), 50);
            $products = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'message' => __('api.popular_products_loaded_successfully'),
                'data' => ProductListResource::collection($products),
                'pagination' => [
                    'current_page' => $products->currentPage(),
                    'last_page' => $products->lastPage(),
                    'per_page' => $products->perPage(),
                    'total' => $products->total(),
                    'has_more_pages' => $products->hasMorePages(),
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('api.error_loading_popular_products'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get user's order history.
     */
    public function orderHistory(Request $request): JsonResponse
    {
        try {
            // Mock data for now - replace with actual order logic
            return response()->json([
                'success' => true,
                'message' => __('api.order_history_loaded_successfully'),
                'data' => [
                    'orders' => [],
                    'message' => 'Order history will be implemented with order management system'
                ],
                'pagination' => [
                    'current_page' => 1,
                    'last_page' => 1,
                    'per_page' => 15,
                    'total' => 0,
                    'has_more_pages' => false,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('api.error_loading_order_history'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create a new order.
     */
    public function createOrder(Request $request): JsonResponse
    {
        try {
            // Mock implementation - replace with actual order creation logic
            return response()->json([
                'success' => true,
                'message' => __('api.order_created_successfully'),
                'data' => [
                    'order_id' => 'ORD-' . time(),
                    'status' => 'pending',
                    'message' => 'Order creation will be implemented with order management system'
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('api.error_creating_order'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get order details.
     */
    public function getOrder(Request $request, $orderId): JsonResponse
    {
        try {
            // Mock implementation
            return response()->json([
                'success' => true,
                'message' => __('api.order_details_loaded_successfully'),
                'data' => [
                    'order_id' => $orderId,
                    'status' => 'pending',
                    'message' => 'Order details will be implemented with order management system'
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('api.error_loading_order_details'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Track order status.
     */
    public function trackOrder(Request $request, $orderId): JsonResponse
    {
        try {
            // Mock implementation
            return response()->json([
                'success' => true,
                'message' => __('api.order_tracking_loaded_successfully'),
                'data' => [
                    'order_id' => $orderId,
                    'status' => 'preparing',
                    'estimated_delivery' => now()->addMinutes(30)->toISOString(),
                    'tracking_steps' => [
                        ['status' => 'confirmed', 'time' => now()->subMinutes(5)->toISOString(), 'completed' => true],
                        ['status' => 'preparing', 'time' => now()->toISOString(), 'completed' => true],
                        ['status' => 'ready', 'time' => null, 'completed' => false],
                        ['status' => 'out_for_delivery', 'time' => null, 'completed' => false],
                        ['status' => 'delivered', 'time' => null, 'completed' => false],
                    ],
                    'message' => 'Order tracking will be implemented with order management system'
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('api.error_loading_order_tracking'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Cancel an order.
     */
    public function cancelOrder(Request $request, $orderId): JsonResponse
    {
        try {
            // Mock implementation
            return response()->json([
                'success' => true,
                'message' => __('api.order_cancelled_successfully'),
                'data' => [
                    'order_id' => $orderId,
                    'status' => 'cancelled',
                    'message' => 'Order cancellation will be implemented with order management system'
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('api.error_cancelling_order'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get user's favorite merchants.
     */
    public function favoriteMerchants(Request $request): JsonResponse
    {
        try {
            // Mock implementation - replace with actual favorites logic
            return response()->json([
                'success' => true,
                'message' => __('api.favorite_merchants_loaded_successfully'),
                'data' => [
                    'favorites' => [],
                    'message' => 'Favorites will be implemented with user management system'
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('api.error_loading_favorite_merchants'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Add merchant to favorites.
     */
    public function addFavoriteMerchant(Request $request, $merchantId): JsonResponse
    {
        try {
            // Mock implementation
            return response()->json([
                'success' => true,
                'message' => __('api.merchant_added_to_favorites_successfully'),
                'data' => [
                    'merchant_id' => $merchantId,
                    'is_favorite' => true,
                    'message' => 'Add to favorites will be implemented with user management system'
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('api.error_adding_merchant_to_favorites'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove merchant from favorites.
     */
    public function removeFavoriteMerchant(Request $request, $merchantId): JsonResponse
    {
        try {
            // Mock implementation
            return response()->json([
                'success' => true,
                'message' => __('api.merchant_removed_from_favorites_successfully'),
                'data' => [
                    'merchant_id' => $merchantId,
                    'is_favorite' => false,
                    'message' => 'Remove from favorites will be implemented with user management system'
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('api.error_removing_merchant_from_favorites'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get user addresses.
     */
    public function getAddresses(Request $request): JsonResponse
    {
        try {
            // Mock implementation
            return response()->json([
                'success' => true,
                'message' => __('api.addresses_loaded_successfully'),
                'data' => [
                    'addresses' => [],
                    'message' => 'Addresses will be implemented with user management system'
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('api.error_loading_addresses'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create new address.
     */
    public function createAddress(Request $request): JsonResponse
    {
        try {
            // Mock implementation
            return response()->json([
                'success' => true,
                'message' => __('api.address_created_successfully'),
                'data' => [
                    'address_id' => time(),
                    'message' => 'Create address will be implemented with user management system'
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('api.error_creating_address'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update address.
     */
    public function updateAddress(Request $request, $addressId): JsonResponse
    {
        try {
            // Mock implementation
            return response()->json([
                'success' => true,
                'message' => __('api.address_updated_successfully'),
                'data' => [
                    'address_id' => $addressId,
                    'message' => 'Update address will be implemented with user management system'
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('api.error_updating_address'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete address.
     */
    public function deleteAddress(Request $request, $addressId): JsonResponse
    {
        try {
            // Mock implementation
            return response()->json([
                'success' => true,
                'message' => __('api.address_deleted_successfully'),
                'data' => [
                    'address_id' => $addressId,
                    'message' => 'Delete address will be implemented with user management system'
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('api.error_deleting_address'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Set default address.
     */
    public function setDefaultAddress(Request $request, $addressId): JsonResponse
    {
        try {
            // Mock implementation
            return response()->json([
                'success' => true,
                'message' => __('api.default_address_set_successfully'),
                'data' => [
                    'address_id' => $addressId,
                    'is_default' => true,
                    'message' => 'Set default address will be implemented with user management system'
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('api.error_setting_default_address'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get cart contents.
     */
    public function getCart(Request $request): JsonResponse
    {
        try {
            // Mock implementation
            return response()->json([
                'success' => true,
                'message' => __('api.cart_loaded_successfully'),
                'data' => [
                    'items' => [],
                    'total' => 0,
                    'subtotal' => 0,
                    'tax' => 0,
                    'delivery_fee' => 0,
                    'discount' => 0,
                    'message' => 'Cart will be implemented with cart management system'
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('api.error_loading_cart'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Add item to cart.
     */
    public function addToCart(Request $request): JsonResponse
    {
        try {
            // Mock implementation
            return response()->json([
                'success' => true,
                'message' => __('api.item_added_to_cart_successfully'),
                'data' => [
                    'cart_item_id' => time(),
                    'message' => 'Add to cart will be implemented with cart management system'
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('api.error_adding_to_cart'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update cart item.
     */
    public function updateCartItem(Request $request, $itemId): JsonResponse
    {
        try {
            // Mock implementation
            return response()->json([
                'success' => true,
                'message' => __('api.cart_item_updated_successfully'),
                'data' => [
                    'cart_item_id' => $itemId,
                    'message' => 'Update cart item will be implemented with cart management system'
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('api.error_updating_cart_item'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove item from cart.
     */
    public function removeFromCart(Request $request, $itemId): JsonResponse
    {
        try {
            // Mock implementation
            return response()->json([
                'success' => true,
                'message' => __('api.cart_item_removed_successfully'),
                'data' => [
                    'cart_item_id' => $itemId,
                    'message' => 'Remove from cart will be implemented with cart management system'
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('api.error_removing_from_cart'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Clear cart.
     */
    public function clearCart(Request $request): JsonResponse
    {
        try {
            // Mock implementation
            return response()->json([
                'success' => true,
                'message' => __('api.cart_cleared_successfully'),
                'data' => [
                    'message' => 'Clear cart will be implemented with cart management system'
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('api.error_clearing_cart'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Apply coupon to cart.
     */
    public function applyCoupon(Request $request): JsonResponse
    {
        try {
            // Mock implementation
            return response()->json([
                'success' => true,
                'message' => __('api.coupon_applied_successfully'),
                'data' => [
                    'coupon_code' => $request->coupon_code ?? 'SAMPLE',
                    'discount' => 10,
                    'message' => 'Apply coupon will be implemented with coupon management system'
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('api.error_applying_coupon'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove coupon from cart.
     */
    public function removeCoupon(Request $request): JsonResponse
    {
        try {
            // Mock implementation
            return response()->json([
                'success' => true,
                'message' => __('api.coupon_removed_successfully'),
                'data' => [
                    'message' => 'Remove coupon will be implemented with coupon management system'
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('api.error_removing_coupon'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get user reviews.
     */
    public function getReviews(Request $request): JsonResponse
    {
        try {
            // Mock implementation
            return response()->json([
                'success' => true,
                'message' => __('api.reviews_loaded_successfully'),
                'data' => [
                    'reviews' => [],
                    'message' => 'Reviews will be implemented with review management system'
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('api.error_loading_reviews'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Review a merchant.
     */
    public function reviewMerchant(Request $request, $merchantId): JsonResponse
    {
        try {
            // Mock implementation
            return response()->json([
                'success' => true,
                'message' => __('api.merchant_review_submitted_successfully'),
                'data' => [
                    'merchant_id' => $merchantId,
                    'review_id' => time(),
                    'message' => 'Merchant review will be implemented with review management system'
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('api.error_submitting_merchant_review'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Review a product.
     */
    public function reviewProduct(Request $request, $productId): JsonResponse
    {
        try {
            // Mock implementation
            return response()->json([
                'success' => true,
                'message' => __('api.product_review_submitted_successfully'),
                'data' => [
                    'product_id' => $productId,
                    'review_id' => time(),
                    'message' => 'Product review will be implemented with review management system'
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('api.error_submitting_product_review'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get user notifications.
     */
    public function getNotifications(Request $request): JsonResponse
    {
        try {
            // Mock implementation
            return response()->json([
                'success' => true,
                'message' => __('api.notifications_loaded_successfully'),
                'data' => [
                    'notifications' => [],
                    'unread_count' => 0,
                    'message' => 'Notifications will be implemented with notification management system'
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('api.error_loading_notifications'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mark notification as read.
     */
    public function markNotificationAsRead(Request $request, $notificationId): JsonResponse
    {
        try {
            // Mock implementation
            return response()->json([
                'success' => true,
                'message' => __('api.notification_marked_as_read_successfully'),
                'data' => [
                    'notification_id' => $notificationId,
                    'is_read' => true,
                    'message' => 'Mark notification as read will be implemented with notification management system'
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('api.error_marking_notification_as_read'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllNotificationsAsRead(Request $request): JsonResponse
    {
        try {
            // Mock implementation
            return response()->json([
                'success' => true,
                'message' => __('api.all_notifications_marked_as_read_successfully'),
                'data' => [
                    'marked_count' => 0,
                    'message' => 'Mark all notifications as read will be implemented with notification management system'
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('api.error_marking_all_notifications_as_read'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get user profile.
     */
    public function getProfile(Request $request): JsonResponse
    {
        try {
            // Mock implementation
            return response()->json([
                'success' => true,
                'message' => __('api.profile_loaded_successfully'),
                'data' => [
                    'user' => [
                        'id' => 1,
                        'name' => 'Sample User',
                        'email' => 'user@example.com',
                        'phone' => '+966501234567',
                        'avatar_url' => 'https://ui-avatars.com/api/?name=Sample+User&background=random&color=fff&size=200',
                    ],
                    'message' => 'Profile will be implemented with user management system'
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('api.error_loading_profile'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update user profile.
     */
    public function updateProfile(Request $request): JsonResponse
    {
        try {
            // Mock implementation
            return response()->json([
                'success' => true,
                'message' => __('api.profile_updated_successfully'),
                'data' => [
                    'message' => 'Update profile will be implemented with user management system'
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('api.error_updating_profile'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Upload user avatar.
     */
    public function uploadAvatar(Request $request): JsonResponse
    {
        try {
            // Mock implementation
            return response()->json([
                'success' => true,
                'message' => __('api.avatar_uploaded_successfully'),
                'data' => [
                    'avatar_url' => 'https://ui-avatars.com/api/?name=Sample+User&background=random&color=fff&size=200',
                    'message' => 'Upload avatar will be implemented with file management system'
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('api.error_uploading_avatar'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete user avatar.
     */
    public function deleteAvatar(Request $request): JsonResponse
    {
        try {
            // Mock implementation
            return response()->json([
                'success' => true,
                'message' => __('api.avatar_deleted_successfully'),
                'data' => [
                    'avatar_url' => null,
                    'message' => 'Delete avatar will be implemented with file management system'
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('api.error_deleting_avatar'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get all products with advanced filtering and sorting.
     */
    public function allProducts(ProductListRequest $request): JsonResponse
    {
        try {
            $query = Product::where('is_available', true)
                ->with(['merchant', 'internalCategory', 'foodNationality']);

            // Apply filters
            if ($request->filled('category_id')) {
                $query->where('internal_category_id', $request->category_id);
            }

            if ($request->filled('food_nationality_id')) {
                $query->where('food_nationality_id', $request->food_nationality_id);
            }

            if ($request->filled('merchant_id')) {
                $query->where('merchant_id', $request->merchant_id);
            }

            if ($request->filled('min_price')) {
                $query->whereRaw('(base_price - (base_price * COALESCE(discount_percentage, 0) / 100)) >= ?', [$request->min_price]);
            }

            if ($request->filled('max_price')) {
                $query->whereRaw('(base_price - (base_price * COALESCE(discount_percentage, 0) / 100)) <= ?', [$request->max_price]);
            }

            if ($request->filled('is_vegetarian')) {
                $query->where('is_vegetarian', $request->boolean('is_vegetarian'));
            }

            if ($request->filled('is_spicy')) {
                $query->where('is_spicy', $request->boolean('is_spicy'));
            }

            if ($request->filled('is_featured')) {
                $query->where('is_featured', $request->boolean('is_featured'));
            }

            if ($request->filled('is_popular')) {
                $query->where('is_popular', $request->boolean('is_popular'));
            }

            // Search functionality
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(name, '$.en')) LIKE ?", ["%{$search}%"])
                      ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(name, '$.ar')) LIKE ?", ["%{$search}%"])
                      ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(description, '$.en')) LIKE ?", ["%{$search}%"])
                      ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(description, '$.ar')) LIKE ?", ["%{$search}%"]);
                });
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'newest');

            switch ($sortBy) {
                case 'price_asc':
                    $query->orderByRaw('(base_price - (base_price * COALESCE(discount_percentage, 0) / 100)) ASC');
                    break;
                case 'price_desc':
                    $query->orderByRaw('(base_price - (base_price * COALESCE(discount_percentage, 0) / 100)) DESC');
                    break;
                case 'rating_desc':
                    $query->orderBy('average_rating', 'desc');
                    break;
                case 'popularity_desc':
                    $query->orderBy('is_popular', 'desc')
                          ->orderBy('total_orders', 'desc');
                    break;
                case 'newest':
                default:
                    $query->orderBy('created_at', 'desc');
                    break;
            }

            // Pagination
            $perPage = $request->get('per_page', 15);
            $products = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'message' => __('api.products_loaded_successfully'),
                'data' => ProductListResource::collection($products),
                'filters_applied' => [
                    'category_id' => $request->category_id,
                    'food_nationality_id' => $request->food_nationality_id,
                    'merchant_id' => $request->merchant_id,
                    'price_range' => [
                        'min' => $request->min_price,
                        'max' => $request->max_price,
                    ],
                    'dietary' => [
                        'is_vegetarian' => $request->is_vegetarian,
                        'is_spicy' => $request->is_spicy,
                    ],
                    'features' => [
                        'is_featured' => $request->is_featured,
                        'is_popular' => $request->is_popular,
                    ],
                    'search' => $request->search,
                    'sort_by' => $sortBy,
                ],
                'pagination' => [
                    'current_page' => $products->currentPage(),
                    'last_page' => $products->lastPage(),
                    'per_page' => $products->perPage(),
                    'total' => $products->total(),
                    'has_more_pages' => $products->hasMorePages(),
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('api.error_loading_products'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get detailed product information with related products.
     */
    public function singleProduct(Request $request, $productId): JsonResponse
    {
        try {
            $product = Product::where('id', $productId)
                ->where('is_available', true)
                ->with([
                    'merchant' => function($query) {
                        $query->where('status', 'active');
                    },
                    'internalCategory',
                    'foodNationality',
                ])
                ->first();

            if (!$product) {
                return response()->json([
                    'success' => false,
                    'message' => __('api.product_not_found'),
                ], 404);
            }

            // Get related products from same merchant and category
            $relatedProducts = Product::where('merchant_id', $product->merchant_id)
                ->where('internal_category_id', $product->internal_category_id)
                ->where('id', '!=', $product->id)
                ->where('is_available', true)
                ->with(['merchant', 'internalCategory', 'foodNationality'])
                ->limit(6)
                ->get();

            // Get similar products from other merchants (same category or food nationality)
            $similarProducts = Product::where('id', '!=', $product->id)
                ->where('is_available', true)
                ->where(function($query) use ($product) {
                    $query->where('internal_category_id', $product->internal_category_id)
                          ->orWhere('food_nationality_id', $product->food_nationality_id);
                })
                ->with(['merchant', 'internalCategory', 'foodNationality'])
                ->limit(6)
                ->get();

            return response()->json([
                'success' => true,
                'message' => __('api.product_details_loaded_successfully'),
                'data' => new ProductDetailResource($product),
                'related_products' => ProductListResource::collection($relatedProducts),
                'similar_products' => ProductListResource::collection($similarProducts),
                'merchant_info' => [
                    'id' => $product->merchant->id,
                    'name' => $product->merchant->getTranslation('business_name', $request->header('X-Language', 'en')),
                    'rating' => $product->merchant->average_rating ?? 4.5,
                    'delivery_fee' => $product->merchant->delivery_fee ?? 0,
                    'minimum_order' => $product->merchant->minimum_order ?? 0,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('api.error_loading_product_details'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
