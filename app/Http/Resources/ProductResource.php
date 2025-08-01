<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Helpers\TranslationHelper;

class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'category_id' => $this->category_id,
            'category' => new CategoryResource($this->whenLoaded('category')),
            'name' => TranslationHelper::formatTranslatable($this, 'name'),
            'description' => $this->when($this->description,
                TranslationHelper::formatTranslatable($this, 'description')
            ),
            'price' => [
                'amount' => (float) $this->price,
                'formatted' => number_format($this->price, 2),
                'currency' => 'USD', // You can make this configurable
            ],
            'discount_price' => $this->when($this->discount_price, [
                'amount' => (float) $this->discount_price,
                'formatted' => number_format($this->discount_price, 2),
                'currency' => 'USD',
            ]),
            'effective_price' => [
                'amount' => (float) $this->effective_price,
                'formatted' => number_format($this->effective_price, 2),
                'currency' => 'USD',
            ],
            'has_discount' => $this->has_discount,
            'discount_percentage' => $this->when($this->has_discount, function () {
                return round((($this->price - $this->discount_price) / $this->price) * 100);
            }),
            'sku' => $this->sku,
            'images' => [
                'main' => $this->main_image_url,
                'all' => $this->image_urls,
            ],
            'preparation_time' => $this->preparation_time,
            'calories' => $this->calories,
            'ingredients' => $this->when($this->ingredients, [
                'en' => $this->getTranslation('ingredients', 'en'),
                'ar' => $this->getTranslation('ingredients', 'ar'),
                'current' => $this->ingredients,
            ]),
            'allergens' => $this->when($this->allergens, [
                'en' => $this->getTranslation('allergens', 'en'),
                'ar' => $this->getTranslation('allergens', 'ar'),
                'current' => $this->allergens,
            ]),
            'dietary_info' => [
                'is_vegetarian' => $this->is_vegetarian,
                'is_vegan' => $this->is_vegan,
                'is_gluten_free' => $this->is_gluten_free,
                'is_spicy' => $this->is_spicy,
            ],
            'availability' => [
                'is_available' => $this->is_available,
                'in_stock' => $this->in_stock,
                'stock_quantity' => $this->when($this->track_stock, $this->stock_quantity),
                'track_stock' => $this->track_stock,
            ],
            'is_featured' => $this->is_featured,
            'sort_order' => $this->sort_order,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
