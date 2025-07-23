<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;
use App\Helpers\ImageHelper;
use Illuminate\Support\Facades\Log;
use App\Helpers\CacheHelper;

class InternalCategory extends Model
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
        'image',
        'sort_order',
        'is_active',
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
            // مسح كاش الفئة المحددة
            \Illuminate\Support\Facades\Cache::forget('category_' . $model->id);
        });

        static::deleted(function ($model) {
            self::clearCache();
            // مسح كاش الفئة المحذوفة
            \Illuminate\Support\Facades\Cache::forget('category_' . $model->id);
        });

        // مسح الكاش عند التحديث
        static::updated(function ($model) {
            self::clearCache();
            \Illuminate\Support\Facades\Cache::forget('category_' . $model->id);
        });

        // مسح الكاش عند الإنشاء
        static::created(function ($model) {
            self::clearCache();
        });
    }

    /**
     * مسح كاش الفئات
     */
    public static function clearCache(): void
    {
        CacheHelper::clearCategories();
        CacheHelper::clearFilament();
    }

    /**
     * Get the products for this category.
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Scope to get only active categories.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get categories ordered by sort order.
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
     * Get the image URL attribute.
     */
    public function getImageUrlAttribute()
    {
        if (!$this->image) {
            return null;
        }

        try {
            // التحقق من وجود الصورة فعلياً
            $imagePath = storage_path('app/public/' . $this->image);
            if (!file_exists($imagePath)) {
                // إذا كانت الصورة غير موجودة، حذف المرجع من قاعدة البيانات
                $this->update(['image' => null]);
                return null;
            }

            return ImageHelper::getUrl($this->image);
        } catch (\Exception $e) {
            Log::warning('Error getting category image URL: ' . $e->getMessage());
            return null;
        }
    }


}
