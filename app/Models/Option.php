<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Translatable\HasTranslations;
use App\Helpers\ImageHelper;

class Option extends Model
{
    use HasFactory, HasTranslations;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'option_group_id',
        'name',
        'price_modifier',
        'image_path',
        'is_available',
        'sort_order',
    ];

    /**
     * The attributes that are translatable.
     *
     * @var array<int, string>
     */
    public $translatable = [
        'name',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price_modifier' => 'decimal:2',
            'is_available' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * Get the option group that owns the option.
     */
    public function optionGroup(): BelongsTo
    {
        return $this->belongsTo(OptionGroup::class);
    }

    /**
     * Get the product through the option group.
     */
    public function product(): BelongsTo
    {
        return $this->optionGroup()->getRelated()->product();
    }

    /**
     * Scope to get only available options.
     */
    public function scopeAvailable($query)
    {
        return $query->where('is_available', true);
    }

    /**
     * Scope to get options ordered by sort order.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
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
     * Get the image URL attribute.
     */
    public function getImageUrlAttribute()
    {
        return $this->image_path ? ImageHelper::getUrl($this->image_path) : null;
    }

    /**
     * Check if this option increases the price.
     */
    public function increasesPrice(): bool
    {
        return $this->price_modifier > 0;
    }

    /**
     * Check if this option decreases the price.
     */
    public function decreasesPrice(): bool
    {
        return $this->price_modifier < 0;
    }

    /**
     * Check if this option is free (no price change).
     */
    public function isFree(): bool
    {
        return $this->price_modifier == 0;
    }

    /**
     * Get formatted price modifier.
     */
    public function getFormattedPriceModifierAttribute(): string
    {
        if ($this->price_modifier > 0) {
            return '+' . number_format($this->price_modifier, 2);
        } elseif ($this->price_modifier < 0) {
            return number_format($this->price_modifier, 2);
        }
        return 'Free';
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Delete image file when model is deleted
        static::deleting(function ($option) {
            if ($option->image_path) {
                ImageHelper::delete($option->image_path);
            }
        });
    }
}
