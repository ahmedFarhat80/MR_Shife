<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SearchHistory extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'search_history';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'customer_id',
        'query',
        'search_type',
        'filters',
        'results_count',
        'language',
        'user_agent',
        'ip_address',
        'user_latitude',
        'user_longitude',
        'clicked_result',
        'clicked_result_type',
        'clicked_result_id',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'filters' => 'array',
            'results_count' => 'integer',
            'clicked_result' => 'boolean',
            'user_latitude' => 'decimal:7',
            'user_longitude' => 'decimal:7',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Get the customer that owns this search history.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the clicked product if applicable.
     */
    public function clickedProduct()
    {
        if ($this->clicked_result_type === 'product' && $this->clicked_result_id) {
            return Product::find($this->clicked_result_id);
        }
        return null;
    }

    /**
     * Get the clicked restaurant if applicable.
     */
    public function clickedRestaurant()
    {
        if ($this->clicked_result_type === 'restaurant' && $this->clicked_result_id) {
            return Merchant::find($this->clicked_result_id);
        }
        return null;
    }

    /**
     * Scope for recent searches.
     */
    public function scopeRecent($query, $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Scope for popular searches.
     */
    public function scopePopular($query, $limit = 10)
    {
        return $query->select('query', 'language')
            ->selectRaw('COUNT(*) as search_count')
            ->groupBy('query', 'language')
            ->orderByDesc('search_count')
            ->limit($limit);
    }

    /**
     * Scope for successful searches (with results).
     */
    public function scopeSuccessful($query)
    {
        return $query->where('results_count', '>', 0);
    }

    /**
     * Scope for searches by language.
     */
    public function scopeByLanguage($query, $language)
    {
        return $query->where('language', $language);
    }

    /**
     * Get trending searches for autocomplete.
     */
    public static function getTrendingSearches($language = 'en', $limit = 10)
    {
        return static::byLanguage($language)
            ->recent(7) // Last 7 days
            ->successful()
            ->popular($limit)
            ->get()
            ->pluck('query')
            ->unique()
            ->values();
    }

    /**
     * Get user's recent searches.
     */
    public static function getUserRecentSearches($customerId, $language = 'en', $limit = 10)
    {
        return static::where('customer_id', $customerId)
            ->byLanguage($language)
            ->recent(30)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->pluck('query')
            ->unique()
            ->values();
    }

    /**
     * Record a search interaction.
     */
    public static function recordSearch(array $data)
    {
        return static::create([
            'customer_id' => $data['customer_id'] ?? null,
            'query' => $data['query'],
            'search_type' => $data['search_type'] ?? 'general',
            'filters' => $data['filters'] ?? null,
            'results_count' => $data['results_count'] ?? 0,
            'language' => $data['language'] ?? 'en',
            'user_agent' => $data['user_agent'] ?? null,
            'ip_address' => $data['ip_address'] ?? null,
            'user_latitude' => $data['user_latitude'] ?? null,
            'user_longitude' => $data['user_longitude'] ?? null,
        ]);
    }

    /**
     * Update search with click interaction.
     */
    public function recordClick($resultType, $resultId)
    {
        $this->update([
            'clicked_result' => true,
            'clicked_result_type' => $resultType,
            'clicked_result_id' => $resultId,
        ]);
    }
}
