<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;
use App\Helpers\ImageHelper;
use App\Helpers\CacheHelper;
use Illuminate\Support\Facades\Log;

class FoodNationality extends Model
{
    use HasFactory, HasTranslations;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'description',
        'icon',
        'is_active',
        'sort_order',
    ];

    /**
     * The attributes that are translatable.
     *
     * @var array<int, string>
     */
    public $translatable = [
        'name',
        'description',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    protected static function boot()
    {
        parent::boot();

        // مسح الكاش عند الإنشاء أو التحديث أو الحذف
        static::saved(function ($model) {
            self::clearCache();
            \Illuminate\Support\Facades\Cache::forget('nationality_' . $model->id);
        });

        static::deleted(function ($model) {
            self::clearCache();
            \Illuminate\Support\Facades\Cache::forget('nationality_' . $model->id);
        });

        static::updated(function ($model) {
            self::clearCache();
            \Illuminate\Support\Facades\Cache::forget('nationality_' . $model->id);
        });

        static::created(function ($model) {
            self::clearCache();
        });
    }

    /**
     * مسح كاش الجنسيات
     */
    public static function clearCache(): void
    {
        CacheHelper::clearNationalities();
    }

    /**
     * Get the products for this food nationality.
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'food_nationality_id');
    }

    /**
     * Scope to get only active food nationalities.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get food nationalities ordered by sort order.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    /**
     * Get the translated name attribute.
     */
    public function getTranslatedNameAttribute()
    {
        return $this->getTranslation('name', app()->getLocale())
            ?: $this->getTranslation('name', 'en')
            ?: 'Unknown';
    }

    /**
     * Get the translated description attribute.
     */
    public function getTranslatedDescriptionAttribute()
    {
        return $this->getTranslation('description', app()->getLocale())
            ?: $this->getTranslation('description', 'en');
    }

    /**
     * Get the icon URL attribute.
     */
    public function getIconUrlAttribute()
    {
        if (!$this->icon) {
            return null;
        }

        try {
            // التحقق من وجود الأيقونة فعلياً
            $iconPath = storage_path('app/public/' . $this->icon);
            if (!file_exists($iconPath)) {
                // إذا كانت الأيقونة غير موجودة، حذف المرجع من قاعدة البيانات
                $this->update(['icon' => null]);
                return null;
            }

            return ImageHelper::getUrl($this->icon);
        } catch (\Exception $e) {
            Log::warning('Error getting nationality icon URL: ' . $e->getMessage());
            return null;
        }
    }
}
