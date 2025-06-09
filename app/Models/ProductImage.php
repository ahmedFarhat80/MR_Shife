<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Translatable\HasTranslations;
use App\Helpers\ImageHelper;

class ProductImage extends Model
{
    use HasFactory, HasTranslations;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'product_id',
        'image_path',
        'alt_text',
        'sort_order',
        'is_primary',
    ];

    /**
     * The attributes that are translatable.
     *
     * @var array<int, string>
     */
    public $translatable = [
        'alt_text',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * Get the product that owns the image.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Scope to get only primary images.
     */
    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    /**
     * Scope to get images ordered by sort order.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('is_primary', 'desc')->orderBy('sort_order');
    }

    /**
     * Get the image URL attribute.
     */
    public function getImageUrlAttribute()
    {
        return ImageHelper::getUrl($this->image_path);
    }

    /**
     * Get the translated alt text attribute.
     */
    public function getTranslatedAltTextAttribute()
    {
        return $this->getTranslation('alt_text', app()->getLocale())
            ?: $this->getTranslation('alt_text', 'en');
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Ensure only one primary image per product
        static::saving(function ($productImage) {
            if ($productImage->is_primary) {
                static::where('product_id', $productImage->product_id)
                    ->where('id', '!=', $productImage->id)
                    ->update(['is_primary' => false]);
            }
        });

        // Delete image file when model is deleted
        static::deleting(function ($productImage) {
            if ($productImage->image_path) {
                ImageHelper::delete($productImage->image_path);
            }
        });
    }
}
