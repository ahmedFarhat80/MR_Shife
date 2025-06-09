<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Merchant;
use App\Models\SearchHistory;
use App\Models\FoodNationality;
use App\Models\InternalCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SearchService
{
    /**
     * Perform unified search across products and restaurants.
     */
    public function unifiedSearch(Request $request): array
    {
        $query = trim($request->get('query', ''));
        $language = $request->header('X-Language', 'en');
        $perPage = min($request->get('per_page', 20), 50);
        $searchType = $request->get('search_type', 'all'); // 'all', 'products', 'restaurants'
        
        // Validate query length
        if (strlen($query) < 2) {
            return [
                'success' => false,
                'message' => __('api.search_query_too_short'),
                'data' => [],
                'suggestions' => $this->getSearchSuggestions($language),
            ];
        }

        $results = [];
        $totalResults = 0;

        // Search Products
        if (in_array($searchType, ['all', 'products'])) {
            $productResults = $this->searchProducts($query, $language, $request);
            $results['products'] = $productResults['data'];
            $totalResults += $productResults['total'];
        }

        // Search Restaurants
        if (in_array($searchType, ['all', 'restaurants'])) {
            $restaurantResults = $this->searchRestaurants($query, $language, $request);
            $results['restaurants'] = $restaurantResults['data'];
            $totalResults += $restaurantResults['total'];
        }

        // Record search in history
        $this->recordSearchHistory($request, $query, $language, $totalResults);

        return [
            'success' => true,
            'message' => __('api.search_completed_successfully'),
            'data' => $results,
            'meta' => [
                'query' => $query,
                'language' => $language,
                'search_type' => $searchType,
                'total_results' => $totalResults,
                'suggestions' => $totalResults === 0 ? $this->getSearchSuggestions($language) : [],
            ],
        ];
    }

    /**
     * Search products with advanced filtering.
     */
    private function searchProducts(string $query, string $language, Request $request): array
    {
        $queryBuilder = Product::where('is_available', true)
            ->with(['merchant', 'internalCategory', 'foodNationality']);

        // Language-aware search
        $queryBuilder->where(function($q) use ($query, $language) {
            if ($language === 'ar') {
                $q->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(name, '$.ar')) LIKE ?", ["%{$query}%"])
                  ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(description, '$.ar')) LIKE ?", ["%{$query}%"]);
            } else {
                $q->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(name, '$.en')) LIKE ?", ["%{$query}%"])
                  ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(description, '$.en')) LIKE ?", ["%{$query}%"]);
            }
        });

        // Apply filters
        $this->applyProductFilters($queryBuilder, $request);

        // Sorting by relevance
        $queryBuilder->orderByRaw("
            CASE
                WHEN JSON_UNQUOTE(JSON_EXTRACT(name, '$.{$language}')) LIKE ? THEN 1
                WHEN JSON_UNQUOTE(JSON_EXTRACT(name, '$.{$language}')) LIKE ? THEN 2
                ELSE 3
            END, is_featured DESC, base_price ASC
        ", ["{$query}%", "%{$query}%"]);

        $perPage = min($request->get('per_page', 10), 20);
        $products = $queryBuilder->paginate($perPage);

        return [
            'data' => $products->items(),
            'total' => $products->total(),
            'pagination' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
            ],
        ];
    }

    /**
     * Search restaurants with advanced filtering.
     */
    private function searchRestaurants(string $query, string $language, Request $request): array
    {
        $queryBuilder = Merchant::where('status', 'active')
            ->with(['products' => function($q) {
                $q->where('is_available', true)->limit(3);
            }]);

        // Language-aware search
        $queryBuilder->where(function($q) use ($query, $language) {
            if ($language === 'ar') {
                $q->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(business_name, '$.ar')) LIKE ?", ["%{$query}%"])
                  ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(business_description, '$.ar')) LIKE ?", ["%{$query}%"]);
            } else {
                $q->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(business_name, '$.en')) LIKE ?", ["%{$query}%"])
                  ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(business_description, '$.en')) LIKE ?", ["%{$query}%"]);
            }
        });

        // Apply filters
        $this->applyRestaurantFilters($queryBuilder, $request);

        // Sorting by relevance
        $queryBuilder->orderByRaw("
            CASE
                WHEN JSON_UNQUOTE(JSON_EXTRACT(business_name, '$.{$language}')) LIKE ? THEN 1
                WHEN JSON_UNQUOTE(JSON_EXTRACT(business_name, '$.{$language}')) LIKE ? THEN 2
                ELSE 3
            END, is_featured DESC
        ", ["{$query}%", "%{$query}%"]);

        $perPage = min($request->get('per_page', 10), 20);
        $restaurants = $queryBuilder->paginate($perPage);

        return [
            'data' => $restaurants->items(),
            'total' => $restaurants->total(),
            'pagination' => [
                'current_page' => $restaurants->currentPage(),
                'last_page' => $restaurants->lastPage(),
                'per_page' => $restaurants->perPage(),
                'total' => $restaurants->total(),
            ],
        ];
    }

    /**
     * Apply product-specific filters.
     */
    private function applyProductFilters($query, Request $request): void
    {
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

        if ($request->filled('is_vegetarian')) {
            $query->where('is_vegetarian', $request->boolean('is_vegetarian'));
        }

        if ($request->filled('is_spicy')) {
            $query->where('is_spicy', $request->boolean('is_spicy'));
        }

        if ($request->filled('has_discount')) {
            if ($request->boolean('has_discount')) {
                $query->where('discount_percentage', '>', 0);
            }
        }
    }

    /**
     * Apply restaurant-specific filters.
     */
    private function applyRestaurantFilters($query, Request $request): void
    {
        if ($request->filled('business_type')) {
            $query->where('business_type', $request->business_type);
        }

        if ($request->filled('is_featured')) {
            $query->where('is_featured', $request->boolean('is_featured'));
        }

        if ($request->filled('location_city')) {
            $query->where('location_city', $request->location_city);
        }

        if ($request->filled('location_area')) {
            $query->where('location_area', $request->location_area);
        }

        // Location-based filtering
        if ($request->filled(['user_lat', 'user_lng', 'radius'])) {
            $userLat = $request->user_lat;
            $userLng = $request->user_lng;
            $radius = $request->radius;

            $query->whereRaw(
                "(6371 * acos(cos(radians(?)) * cos(radians(location_latitude)) * cos(radians(location_longitude) - radians(?)) + sin(radians(?)) * sin(radians(location_latitude)))) <= ?",
                [$userLat, $userLng, $userLat, $radius]
            );
        }
    }

    /**
     * Get search suggestions for autocomplete.
     */
    public function getSearchSuggestions(string $language = 'en', int $limit = 10): array
    {
        $suggestions = [];

        // Get trending searches
        $trendingSearches = SearchHistory::getTrendingSearches($language, 5);
        if ($trendingSearches->isNotEmpty()) {
            $suggestions['trending'] = $trendingSearches->toArray();
        }

        // Get popular products
        $popularProducts = Product::where('is_available', true)
            ->where('is_featured', true)
            ->limit(5)
            ->get()
            ->map(function($product) use ($language) {
                return $product->getTranslation('name', $language);
            })
            ->filter()
            ->values()
            ->toArray();

        if (!empty($popularProducts)) {
            $suggestions['popular_products'] = $popularProducts;
        }

        // Get popular restaurants
        $popularRestaurants = Merchant::where('status', 'active')
            ->where('is_featured', true)
            ->limit(5)
            ->get()
            ->map(function($merchant) use ($language) {
                return $merchant->getTranslation('business_name', $language);
            })
            ->filter()
            ->values()
            ->toArray();

        if (!empty($popularRestaurants)) {
            $suggestions['popular_restaurants'] = $popularRestaurants;
        }

        // Get food categories
        $categories = FoodNationality::limit(5)
            ->get()
            ->map(function($category) use ($language) {
                return $category->getTranslation('name', $language);
            })
            ->filter()
            ->values()
            ->toArray();

        if (!empty($categories)) {
            $suggestions['categories'] = $categories;
        }

        return $suggestions;
    }

    /**
     * Get autocomplete suggestions based on partial query.
     */
    public function getAutocompleteSuggestions(string $query, string $language = 'en', int $limit = 10): array
    {
        if (strlen($query) < 2) {
            return $this->getSearchSuggestions($language, $limit);
        }

        $suggestions = [];

        // Search in products
        $productSuggestions = Product::where('is_available', true)
            ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(name, '$.{$language}')) LIKE ?", ["{$query}%"])
            ->limit($limit)
            ->get()
            ->map(function($product) use ($language) {
                return [
                    'text' => $product->getTranslation('name', $language),
                    'type' => 'product',
                    'id' => $product->id,
                ];
            })
            ->toArray();

        // Search in restaurants
        $restaurantSuggestions = Merchant::where('status', 'active')
            ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(business_name, '$.{$language}')) LIKE ?", ["{$query}%"])
            ->limit($limit)
            ->get()
            ->map(function($merchant) use ($language) {
                return [
                    'text' => $merchant->getTranslation('business_name', $language),
                    'type' => 'restaurant',
                    'id' => $merchant->id,
                ];
            })
            ->toArray();

        // Merge and sort by relevance
        $allSuggestions = array_merge($productSuggestions, $restaurantSuggestions);
        
        // Sort by exact match first, then alphabetically
        usort($allSuggestions, function($a, $b) use ($query) {
            $aStarts = stripos($a['text'], $query) === 0;
            $bStarts = stripos($b['text'], $query) === 0;
            
            if ($aStarts && !$bStarts) return -1;
            if (!$aStarts && $bStarts) return 1;
            
            return strcasecmp($a['text'], $b['text']);
        });

        return array_slice($allSuggestions, 0, $limit);
    }

    /**
     * Record search in history.
     */
    private function recordSearchHistory(Request $request, string $query, string $language, int $resultsCount): void
    {
        $customer = $request->user('sanctum');
        
        if ($customer) {
            SearchHistory::recordSearch([
                'customer_id' => $customer->id,
                'query' => $query,
                'search_type' => $request->get('search_type', 'all'),
                'filters' => $request->except(['query', 'search_type', 'per_page', 'page']),
                'results_count' => $resultsCount,
                'language' => $language,
                'user_agent' => $request->userAgent(),
                'ip_address' => $request->ip(),
                'user_latitude' => $request->get('user_lat'),
                'user_longitude' => $request->get('user_lng'),
            ]);
        }
    }
}
