<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SearchRequest;
use App\Http\Requests\AutocompleteRequest;
use App\Services\SearchService;
use App\Models\SearchHistory;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SearchController extends Controller
{
    protected SearchService $searchService;

    public function __construct(SearchService $searchService)
    {
        $this->searchService = $searchService;
    }

    /**
     * Unified search endpoint for products and restaurants.
     */
    public function search(SearchRequest $request): JsonResponse
    {
        try {
            $result = $this->searchService->unifiedSearch($request);
            
            return response()->json($result, $result['success'] ? 200 : 400);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('api.search_failed'),
                'error' => config('app.debug') ? $e->getMessage() : __('api.internal_server_error'),
            ], 500);
        }
    }

    /**
     * Get autocomplete suggestions.
     */
    public function autocomplete(AutocompleteRequest $request): JsonResponse
    {
        try {
            $query = $request->get('query', '');
            $language = $request->header('X-Language', 'en');
            $limit = $request->get('limit', 10);

            $suggestions = $this->searchService->getAutocompleteSuggestions($query, $language, $limit);

            return response()->json([
                'success' => true,
                'message' => __('api.autocomplete_suggestions_retrieved'),
                'data' => [
                    'suggestions' => $suggestions,
                    'query' => $query,
                    'language' => $language,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('api.autocomplete_failed'),
                'error' => config('app.debug') ? $e->getMessage() : __('api.internal_server_error'),
            ], 500);
        }
    }

    /**
     * Get search suggestions (trending, popular, etc.).
     */
    public function suggestions(Request $request): JsonResponse
    {
        try {
            $language = $request->header('X-Language', 'en');
            $limit = min($request->get('limit', 10), 20);

            $suggestions = $this->searchService->getSearchSuggestions($language, $limit);

            return response()->json([
                'success' => true,
                'message' => __('api.search_suggestions_retrieved'),
                'data' => [
                    'suggestions' => $suggestions,
                    'language' => $language,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('api.suggestions_failed'),
                'error' => config('app.debug') ? $e->getMessage() : __('api.internal_server_error'),
            ], 500);
        }
    }

    /**
     * Get user's search history (requires authentication).
     */
    public function history(Request $request): JsonResponse
    {
        try {
            $customer = $request->user('sanctum');
            
            if (!$customer) {
                return response()->json([
                    'success' => false,
                    'message' => __('api.authentication_required'),
                ], 401);
            }

            $language = $request->header('X-Language', 'en');
            $limit = min($request->get('limit', 20), 50);

            $history = SearchHistory::where('customer_id', $customer->id)
                ->byLanguage($language)
                ->recent(30)
                ->orderByDesc('created_at')
                ->limit($limit)
                ->get()
                ->map(function($item) {
                    return [
                        'id' => $item->id,
                        'query' => $item->query,
                        'search_type' => $item->search_type,
                        'results_count' => $item->results_count,
                        'searched_at' => $item->created_at->toISOString(),
                    ];
                });

            return response()->json([
                'success' => true,
                'message' => __('api.search_history_retrieved'),
                'data' => [
                    'history' => $history,
                    'total_count' => $history->count(),
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('api.search_history_failed'),
                'error' => config('app.debug') ? $e->getMessage() : __('api.internal_server_error'),
            ], 500);
        }
    }

    /**
     * Delete specific search history item (requires authentication).
     */
    public function deleteHistoryItem(Request $request, int $historyId): JsonResponse
    {
        try {
            $customer = $request->user('sanctum');
            
            if (!$customer) {
                return response()->json([
                    'success' => false,
                    'message' => __('api.authentication_required'),
                ], 401);
            }

            $historyItem = SearchHistory::where('id', $historyId)
                ->where('customer_id', $customer->id)
                ->first();

            if (!$historyItem) {
                return response()->json([
                    'success' => false,
                    'message' => __('api.search_history_item_not_found'),
                ], 404);
            }

            $historyItem->delete();

            return response()->json([
                'success' => true,
                'message' => __('api.search_history_item_deleted'),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('api.delete_search_history_failed'),
                'error' => config('app.debug') ? $e->getMessage() : __('api.internal_server_error'),
            ], 500);
        }
    }

    /**
     * Clear all search history (requires authentication).
     */
    public function clearHistory(Request $request): JsonResponse
    {
        try {
            $customer = $request->user('sanctum');
            
            if (!$customer) {
                return response()->json([
                    'success' => false,
                    'message' => __('api.authentication_required'),
                ], 401);
            }

            $deletedCount = SearchHistory::where('customer_id', $customer->id)->delete();

            return response()->json([
                'success' => true,
                'message' => __('api.search_history_cleared'),
                'data' => [
                    'deleted_count' => $deletedCount,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('api.clear_search_history_failed'),
                'error' => config('app.debug') ? $e->getMessage() : __('api.internal_server_error'),
            ], 500);
        }
    }

    /**
     * Record search result click (for analytics).
     */
    public function recordClick(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'search_history_id' => 'required|integer|exists:search_history,id',
                'result_type' => 'required|string|in:product,restaurant',
                'result_id' => 'required|integer',
            ]);

            $customer = $request->user('sanctum');
            
            if (!$customer) {
                return response()->json([
                    'success' => false,
                    'message' => __('api.authentication_required'),
                ], 401);
            }

            $searchHistory = SearchHistory::where('id', $request->search_history_id)
                ->where('customer_id', $customer->id)
                ->first();

            if (!$searchHistory) {
                return response()->json([
                    'success' => false,
                    'message' => __('api.search_history_item_not_found'),
                ], 404);
            }

            $searchHistory->recordClick($request->result_type, $request->result_id);

            return response()->json([
                'success' => true,
                'message' => __('api.search_click_recorded'),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('api.record_click_failed'),
                'error' => config('app.debug') ? $e->getMessage() : __('api.internal_server_error'),
            ], 500);
        }
    }
}
