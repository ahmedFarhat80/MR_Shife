<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CustomerResource;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\ProductResource;
use App\Http\Resources\MobileProductResource;
use App\Models\Customer;
use App\Models\InternalCategory;
use App\Models\Product;
use App\Services\CustomerAnalyticsService;
use App\Services\ApiResponseService;
use App\Helpers\ImageHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CustomerController extends Controller
{
    protected CustomerAnalyticsService $analyticsService;
    protected ApiResponseService $apiResponse;

    public function __construct(CustomerAnalyticsService $analyticsService, ApiResponseService $apiResponse)
    {
        $this->analyticsService = $analyticsService;
        $this->apiResponse = $apiResponse;
    }

    /**
     * Get customer profile with calculated statistics.
     */
    public function profile(Request $request): JsonResponse
    {
        $customer = $request->user();

        return response()->json([
            'success' => true,
            'message' => __('api.customer_profile_retrieved'),
            'data' => new CustomerResource($customer),
        ]);
    }

    /**
     * Update customer profile
     */
    public function updateProfile(Request $request)
    {
        $customer = $request->user();

        $validator = Validator::make($request->all(), [
            'name_en' => 'nullable|string|max:255',
            'name_ar' => 'nullable|string|max:255',
            'email' => 'nullable|email|unique:customers,email,' . $customer->id,
            'preferred_language' => 'nullable|string|in:ar,en',
            'avatar' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'date_of_birth' => 'nullable|date|before:today',
            'gender' => 'nullable|string|in:male,female,other',
            'addresses' => 'nullable|array',
            'addresses.*.address_line' => 'required_with:addresses|string|max:255',
            'addresses.*.city' => 'required_with:addresses|string|max:100',
            'addresses.*.area' => 'nullable|string|max:100',
            'addresses.*.building' => 'nullable|string|max:100',
            'addresses.*.floor' => 'nullable|string|max:50',
            'addresses.*.apartment' => 'nullable|string|max:50',
            'addresses.*.latitude' => 'nullable|numeric|between:-90,90',
            'addresses.*.longitude' => 'nullable|numeric|between:-180,180',
            'addresses.*.notes' => 'nullable|string|max:500',
            'addresses.*.is_default' => 'nullable|boolean',
            'default_address' => 'nullable|array',
            'default_address.address_line' => 'required_with:default_address|string|max:255',
            'default_address.city' => 'required_with:default_address|string|max:100',
            'default_address.area' => 'nullable|string|max:100',
            'default_address.building' => 'nullable|string|max:100',
            'default_address.floor' => 'nullable|string|max:50',
            'default_address.apartment' => 'nullable|string|max:50',
            'default_address.latitude' => 'nullable|numeric|between:-90,90',
            'default_address.longitude' => 'nullable|numeric|between:-180,180',
            'default_address.notes' => 'nullable|string|max:500',
            'notifications_enabled' => 'nullable|boolean',
            'sms_notifications' => 'nullable|boolean',
            'email_notifications' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return $this->apiResponse->validationError($validator->errors());
        }

        try {
            DB::beginTransaction();

            $updateData = [];

            // Handle name updates
            if ($request->has('name_en') || $request->has('name_ar')) {
                $updateData['name'] = [
                    'en' => $request->name_en ?? $customer->getTranslation('name', 'en'),
                    'ar' => $request->name_ar ?? $customer->getTranslation('name', 'ar'),
                ];
            }

            // Handle avatar upload
            if ($request->hasFile('avatar')) {
                $uploadResult = ImageHelper::uploadWithResize(
                    $request->file('avatar'),
                    'user_avatars',
                    'medium',
                    'public',
                    $customer->avatar
                );

                if ($uploadResult['success']) {
                    $updateData['avatar'] = $uploadResult['main_path'];
                } else {
                    throw new \Exception("Failed to upload avatar: " . $uploadResult['message']);
                }
            }

            // Handle other fields
            $fields = ['email', 'preferred_language', 'date_of_birth', 'gender', 'addresses',
                      'default_address', 'notifications_enabled', 'sms_notifications',
                      'email_notifications'];

            foreach ($fields as $field) {
                if ($request->has($field)) {
                    $updateData[$field] = $request->$field;
                }
            }

            // Reset email verification if email changed
            if (isset($updateData['email']) && $updateData['email'] !== $customer->email) {
                $updateData['email_verified'] = false;
                $updateData['email_verified_at'] = null;
            }

            $customer->update($updateData);

            DB::commit();

            return $this->apiResponse->success(
                __('customer.profile_updated_successfully'),
                [
                    'customer' => new CustomerResource($customer->fresh()),
                ]
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->apiResponse->error(__('customer.profile_update_failed') . ': ' . $e->getMessage());
        }
    }

    /**
     * Update customer avatar (handles both upload new and update existing)
     */
    public function updateAvatar(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'avatar' => 'required|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        if ($validator->fails()) {
            return $this->apiResponse->validationError($validator->errors());
        }

        $customer = $request->user();

        try {
            // Delete old avatar if exists
            if ($customer->avatar) {
                $oldPath = storage_path('app/public/' . $customer->avatar);
                $oldPublicPath = public_path('storage/' . $customer->avatar);

                if (file_exists($oldPath)) {
                    unlink($oldPath);
                }
                if (file_exists($oldPublicPath)) {
                    unlink($oldPublicPath);
                }
            }

            // Upload new avatar using direct file operations
            $file = $request->file('avatar');
            $filename = 'customer_' . $customer->id . '_' . time() . '.' . $file->getClientOriginalExtension();

            // Ensure directory exists
            $uploadDir = storage_path('app/public/user_avatars');
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $fullPath = $uploadDir . '/' . $filename;
            $avatarPath = 'user_avatars/' . $filename;

            // Move uploaded file directly
            if (move_uploaded_file($file->getPathname(), $fullPath)) {
                // Also copy to public/storage for web access
                $publicPath = public_path('storage/user_avatars/' . $filename);
                $publicDir = dirname($publicPath);
                if (!is_dir($publicDir)) {
                    mkdir($publicDir, 0755, true);
                }
                copy($fullPath, $publicPath);

                // Verify file was actually saved
                if (file_exists($fullPath) && file_exists($publicPath)) {
                    $customer->update(['avatar' => $avatarPath]);

                    return $this->apiResponse->success(
                        __('customer.avatar_updated_successfully'),
                        [
                            'customer' => new CustomerResource($customer->fresh()),
                            'avatar_url' => $customer->fresh()->avatar_url,
                        ]
                    );
                } else {
                    return $this->apiResponse->error(__('customer.avatar_update_failed') . ': File not saved');
                }
            } else {
                return $this->apiResponse->error(__('customer.avatar_update_failed') . ': Upload failed');
            }
        } catch (\Exception $e) {
            return $this->apiResponse->error(__('customer.avatar_update_failed') . ': ' . $e->getMessage());
        }
    }

    /**
     * Delete customer avatar
     */
    public function deleteAvatar(Request $request)
    {
        $customer = $request->user();

        try {
            if ($customer->avatar) {
                // Delete the avatar files from both locations
                $storagePath = storage_path('app/public/' . $customer->avatar);
                $publicPath = public_path('storage/' . $customer->avatar);

                if (file_exists($storagePath)) {
                    unlink($storagePath);
                }
                if (file_exists($publicPath)) {
                    unlink($publicPath);
                }

                // Update customer record
                $customer->update(['avatar' => null]);

                return $this->apiResponse->success(
                    __('customer.avatar_deleted_successfully'),
                    [
                        'customer' => new CustomerResource($customer->fresh()),
                        'avatar_url' => $customer->fresh()->avatar_url, // Will return default avatar
                    ]
                );
            } else {
                return $this->apiResponse->error(__('customer.no_avatar_to_delete'));
            }
        } catch (\Exception $e) {
            return $this->apiResponse->error(__('customer.avatar_delete_failed') . ': ' . $e->getMessage());
        }
    }

    /**
     * Get customer dashboard with analytics.
     */
    public function dashboard(Request $request): JsonResponse
    {
        $customer = $request->user();
        $dashboard = $this->analyticsService->getCustomerDashboard($customer);

        return response()->json([
            'success' => true,
            'message' => __('api.dashboard_data_retrieved'),
            'data' => $dashboard,
        ]);
    }

    /**
     * Get customer overview statistics.
     */
    public function overview(Request $request): JsonResponse
    {
        $customer = $request->user();
        $overview = $this->analyticsService->getCustomerOverview($customer);

        return response()->json([
            'success' => true,
            'message' => __('api.overview_retrieved'),
            'data' => $overview,
        ]);
    }

    /**
     * Get customer spending analytics.
     */
    public function spendingAnalytics(Request $request): JsonResponse
    {
        $customer = $request->user();
        $analytics = $this->analyticsService->getSpendingAnalytics($customer);

        return response()->json([
            'success' => true,
            'message' => __('api.spending_analytics_retrieved'),
            'data' => $analytics,
        ]);
    }

    /**
     * Get customer order patterns.
     */
    public function orderPatterns(Request $request): JsonResponse
    {
        $customer = $request->user();
        $patterns = $this->analyticsService->getOrderPatterns($customer);

        return response()->json([
            'success' => true,
            'message' => __('api.order_patterns_retrieved'),
            'data' => $patterns,
        ]);
    }

    /**
     * Get customer loyalty status.
     */
    public function loyaltyStatus(Request $request): JsonResponse
    {
        $customer = $request->user();
        $loyaltyStatus = $this->analyticsService->getLoyaltyStatus($customer);

        return response()->json([
            'success' => true,
            'message' => __('api.loyalty_status_retrieved'),
            'data' => $loyaltyStatus,
        ]);
    }

    /**
     * Update loyalty points based on current spending.
     */
    public function updateLoyaltyPoints(Request $request): JsonResponse
    {
        $customer = $request->user();
        $customer->updateLoyaltyPoints();

        return response()->json([
            'success' => true,
            'message' => __('api.loyalty_points_updated'),
            'data' => [
                'loyalty_points' => $customer->fresh()->loyalty_points,
                'customer_tier' => $customer->customer_tier,
            ],
        ]);
    }

    /**
     * Get customer statistics summary.
     */
    public function statistics(Request $request): JsonResponse
    {
        $customer = $request->user();

        $statistics = [
            'basic_stats' => [
                'total_orders' => $customer->total_orders,
                'completed_orders' => $customer->completed_orders_count,
                'total_spent' => $customer->total_spent,
                'average_order_value' => $customer->average_order_value,
                'loyalty_points' => $customer->loyalty_points,
            ],
            'tier_info' => [
                'current_tier' => $customer->customer_tier,
                'is_frequent_buyer' => $customer->isFrequentBuyer(),
                'account_age_days' => $customer->created_at->diffInDays(now()),
            ],
            'recent_activity' => [
                'recent_orders_count' => $customer->recent_orders->count(),
                'last_order_date' => $customer->orders()->latest()->first()?->created_at?->format('Y-m-d H:i:s'),
                'last_login' => $customer->last_login_at?->format('Y-m-d H:i:s'),
            ],
            'favorite_merchant' => $customer->getFavoriteMerchant() ? [
                'id' => $customer->getFavoriteMerchant()->id,
                'name' => $customer->getFavoriteMerchant()->getTranslations('name'),
            ] : null,
        ];

        return response()->json([
            'success' => true,
            'message' => __('api.customer_statistics_retrieved'),
            'data' => $statistics,
        ]);
    }

    /**
     * Get all customers with their calculated statistics (Admin only).
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->get('per_page', 15);
        $search = $request->get('search');
        $status = $request->get('status');
        $tier = $request->get('tier');

        $query = Customer::query();

        // Search by name, phone, or email
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('phone_number', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhereRaw("JSON_EXTRACT(name, '$.en') LIKE ?", ["%{$search}%"])
                  ->orWhereRaw("JSON_EXTRACT(name, '$.ar') LIKE ?", ["%{$search}%"]);
            });
        }

        // Filter by status
        if ($status) {
            $query->where('status', $status);
        }

        // Filter by tier (calculated dynamically)
        if ($tier) {
            // This is more complex since tier is calculated
            // We'll need to filter based on total_spent ranges
            $tierRanges = [
                'bronze' => [0, 499.99],
                'silver' => [500, 1999.99],
                'gold' => [2000, 4999.99],
                'platinum' => [5000, PHP_FLOAT_MAX],
            ];

            if (isset($tierRanges[$tier])) {
                [$min, $max] = $tierRanges[$tier];

                $query->whereHas('orders', function ($q) use ($min, $max) {
                    $q->where('status', 'delivered')
                      ->havingRaw('SUM(total_amount) >= ? AND SUM(total_amount) <= ?', [$min, $max]);
                }, '>=', 1);
            }
        }

        $customers = $query->latest()->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => __('api.customers_retrieved'),
            'data' => CustomerResource::collection($customers),
            'pagination' => [
                'current_page' => $customers->currentPage(),
                'last_page' => $customers->lastPage(),
                'per_page' => $customers->perPage(),
                'total' => $customers->total(),
            ],
        ]);
    }

    /**
     * Get specific customer details (Admin only).
     */
    public function show(Customer $customer): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => __('api.customer_details_retrieved'),
            'data' => new CustomerResource($customer),
        ]);
    }

    /**
     * Get customer analytics dashboard (Admin only).
     */
    public function adminDashboard(Customer $customer): JsonResponse
    {
        $dashboard = $this->analyticsService->getCustomerDashboard($customer);

        return response()->json([
            'success' => true,
            'message' => __('api.customer_analytics_retrieved'),
            'data' => $dashboard,
        ]);
    }

    // ========================================
    // 🏷️ CATEGORY METHODS (Simplified)
    // ========================================

    /**
     * Get all categories - used everywhere in the app
     * Can be used for: home filter, category chips, category list, etc.
     */
    public function getCategories(Request $request)
    {
        try {
            $cacheKey = 'categories_' . app()->getLocale();

            $categories = Cache::remember($cacheKey, 3600, function () {
                return InternalCategory::where('is_active', true)
                    ->mainCategories()
                    ->with(['children' => function ($query) {
                        $query->where('is_active', true)->orderBy('sort_order');
                    }])
                    ->withCount(['products' => function ($query) {
                        $query->where('is_available', true);
                    }])
                    ->orderBy('sort_order')
                    ->get()
                    ->map(function ($category) {
                        return [
                            'id' => $category->id,
                            'name' => $category->name,
                            'description' => $category->description,
                            'image' => $category->image ? asset('storage/' . $category->image) : null,
                            'level' => $category->level,
                            'products_count' => $category->products_count,
                            'sort_order' => $category->sort_order,
                            'sub_categories' => $category->children->map(function ($subCategory) {
                                return [
                                    'id' => $subCategory->id,
                                    'name' => $subCategory->name,
                                    'description' => $subCategory->description,
                                    'image' => $subCategory->image ? asset('storage/' . $subCategory->image) : null,
                                    'level' => $subCategory->level,
                                    'parent_id' => $subCategory->parent_id,
                                    'sort_order' => $subCategory->sort_order,
                                ];
                            })
                        ];
                    });
            });

            // Add "Popular" as first item (special category)
            $allCategories = collect([
                [
                    'id' => 0,
                    'name' => __('Popular'),
                    'description' => __('Most popular items'),
                    'image' => null,
                    'products_count' => 0,
                    'sort_order' => 0,
                ]
            ])->merge($categories);

            return response()->json([
                'success' => true,
                'message' => __('categories.retrieved_successfully'),
                'data' => $allCategories
            ]);

        } catch (\Exception $e) {
            Log::error('Get categories error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => __('categories.failed_to_retrieve'),
                'error' => $e->getMessage()
            ], 500);
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
                ->with(['merchant', 'images', 'internalCategory', 'subCategory', 'foodNationality', 'optionGroups.options']);

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

            return response()->json([
                'success' => true,
                'message' => __('categories.products_retrieved_successfully'),
                'data' => [
                    'category' => [
                        'id' => $category->id,
                        'name' => $category->name,
                        'description' => $category->description,
                    ],
                    'products' => MobileProductResource::collection($products->items()),
                    'pagination' => [
                        'current_page' => $products->currentPage(),
                        'last_page' => $products->lastPage(),
                        'per_page' => $products->perPage(),
                        'total' => $products->total(),
                        'has_more' => $products->hasMorePages(),
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Get category products error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => __('Failed to retrieve category products'),
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // ========================================
    // 🛍️ PRODUCT METHODS (Matching Mobile App)
    // ========================================

    /**
     * Get all products with filtering and sorting
     * Used for: home sections, category products, search results
     */
    public function getProducts(Request $request)
    {
        try {
            $perPage = $request->get('per_page', 20);
            $sortBy = $request->get('sort_by', 'created_at'); // created_at, price, rating, name, popular
            $sortOrder = $request->get('sort_order', 'desc'); // asc, desc
            $categoryId = $request->get('category_id'); // filter by category
            $section = $request->get('section'); // best_seller, back_again, popular
            $search = $request->get('search'); // search query

            $query = Product::where('is_available', true)
                ->with(['merchant', 'images', 'internalCategory', 'subCategory', 'foodNationality', 'optionGroups.options']);

            // Apply category filter
            if ($categoryId) {
                $query->where('internal_category_id', $categoryId);
            }

            // Apply section filter
            if ($section) {
                switch ($section) {
                    case 'best_seller':
                        $query->where('is_featured', true)
                              ->orderBy('total_sales', 'desc');
                        break;
                    case 'back_again':
                        // Products user has ordered before (for now, just popular ones)
                        $query->where('average_rating', '>=', 4.0)
                              ->orderBy('average_rating', 'desc');
                        break;
                    case 'popular':
                        $query->orderBy('total_sales', 'desc')
                              ->orderBy('average_rating', 'desc');
                        break;
                }
            }

            // Apply search filter
            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            }

            // Apply sorting (if not already applied by section)
            if (!$section) {
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
                    case 'popular':
                        $query->orderBy('total_sales', 'desc')
                              ->orderBy('average_rating', 'desc');
                        break;
                    default:
                        $query->orderBy('created_at', $sortOrder);
                }
            }

            $products = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'message' => __('products.retrieved_successfully'),
                'data' => [
                    'products' => MobileProductResource::collection($products->items()),
                    'pagination' => [
                        'current_page' => $products->currentPage(),
                        'last_page' => $products->lastPage(),
                        'per_page' => $products->perPage(),
                        'total' => $products->total(),
                        'has_more' => $products->hasMorePages(),
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Get products error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => __('products.failed_to_retrieve'),
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get product details
     * Used for: product details screen
     */
    public function getProduct(Request $request, $productId)
    {
        try {
            $product = Product::where('is_available', true)
                ->with(['merchant', 'images', 'internalCategory', 'subCategory', 'foodNationality', 'optionGroups.options'])
                ->findOrFail($productId);

            return response()->json([
                'success' => true,
                'message' => __('products.details_retrieved_successfully'),
                'data' => new MobileProductResource($product)
            ]);

        } catch (\Exception $e) {
            Log::error('Get product details error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => __('Failed to retrieve product details'),
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Search products
     * Used for: search functionality
     */
    public function search(Request $request)
    {
        try {
            $query = $request->get('q', '');
            $perPage = $request->get('per_page', 20);
            $categoryId = $request->get('category_id');

            if (empty($query)) {
                return response()->json([
                    'success' => true,
                    'message' => __('products.search_query_required'),
                    'data' => [
                        'products' => [],
                        'pagination' => [
                            'current_page' => 1,
                            'last_page' => 1,
                            'per_page' => $perPage,
                            'total' => 0,
                            'has_more' => false,
                        ]
                    ]
                ]);
            }

            // Use the same getProducts method with search parameter
            $request->merge(['search' => $query]);
            return $this->getProducts($request);

        } catch (\Exception $e) {
            Log::error('Search products error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => __('products.failed_to_search'),
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
