<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MobileProductResource extends JsonResource
{
    /**
     * Transform the resource into an array compatible with Flutter app.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Calculate effective price (with discount if applicable)
        $basePrice = (float) $this->base_price;
        $discountPercentage = (float) ($this->discount_percentage ?? 0);
        $discountedPrice = $this->discounted_price ? (float) $this->discounted_price : null;
        $effectivePrice = $discountedPrice ?? $basePrice;
        $hasDiscount = $discountPercentage > 0 || $discountedPrice !== null;

        return [
            'id' => $this->id,
            'merchant_id' => $this->merchant_id,
            'merchant' => $this->whenLoaded('merchant', function () {
                return [
                    'id' => $this->merchant->id,
                    'name' => $this->merchant->business_name ?? $this->merchant->name,
                    'business_name' => $this->merchant->business_name,
                ];
            }),
            'internal_category_id' => $this->internal_category_id,
            'internal_category' => $this->whenLoaded('internalCategory', function () {
                return [
                    'id' => $this->internalCategory->id,
                    'name' => $this->internalCategory->name,
                    'level' => $this->internalCategory->level,
                ];
            }),
            'sub_category_id' => $this->sub_category_id,
            'sub_category' => $this->whenLoaded('subCategory', function () {
                return [
                    'id' => $this->subCategory->id,
                    'name' => $this->subCategory->name,
                    'level' => $this->subCategory->level,
                ];
            }),
            'food_nationality_id' => $this->food_nationality_id,
            'food_nationality' => $this->whenLoaded('foodNationality', function () {
                return [
                    'id' => $this->foodNationality->id,
                    'name' => $this->foodNationality->name,
                ];
            }),

            // Basic Information (Flutter compatible)
            'name' => $this->name, // Already translated by model
            'description' => $this->description ?? '',

            // Pricing Information (Flutter compatible)
            'price' => $effectivePrice,
            'originalPrice' => $hasDiscount ? $basePrice : null,
            'base_price' => $basePrice,
            'discount_percentage' => $discountPercentage,
            'discounted_price' => $discountedPrice,
            'effective_price' => $effectivePrice,
            'has_discount' => $hasDiscount,
            'currency' => 'SAR',

            // Images (Flutter compatible)
            'image' => $this->whenLoaded('images', function () {
                return $this->images->count() > 0 ? $this->images->first()->image_url : '/images/placeholder-product.jpg';
            }, '/images/placeholder-product.jpg'),
            'images' => $this->whenLoaded('images', function () {
                return $this->images->pluck('image_url')->toArray();
            }, []),

            // Product Details
            'sku' => $this->sku,
            'preparation_time' => $this->preparation_time ?? 15,
            'calories' => $this->calories,
            'ingredients' => $this->ingredients ?? [],
            'allergens' => $this->allergens ?? [],

            // Dietary Information
            'is_vegetarian' => $this->is_vegetarian,
            'is_vegan' => $this->is_vegan,
            'is_gluten_free' => $this->is_gluten_free,
            'is_spicy' => $this->is_spicy,

            // Availability & Stock
            'is_available' => $this->is_available,
            'stock_quantity' => $this->stock_quantity,
            'track_stock' => $this->track_stock,
            'in_stock' => $this->track_stock ? $this->stock_quantity > 0 : true,

            // Features & Ratings (Flutter compatible)
            'is_featured' => $this->is_featured,
            'rating' => (float) ($this->average_rating ?? 0),
            'reviewCount' => (int) ($this->total_orders ?? 0),
            'average_rating' => (float) ($this->average_rating ?? 0),
            'total_orders' => (int) ($this->total_orders ?? 0),
            'is_popular' => $this->is_popular ?? false,

            // Flutter specific fields
            'productCode' => $this->sku ?? "PRD-{$this->id}",
            'sizes' => $this->whenLoaded('optionGroups', function () {
                $sizeGroup = $this->optionGroups->where('type', 'size')->first();
                if ($sizeGroup && $sizeGroup->options && $sizeGroup->options->count() > 0) {
                    return $sizeGroup->options->map(function ($option) {
                        return [
                            'id' => $option->id,
                            'name' => $option->name,
                            'price_modifier' => (float) $option->price_modifier,
                            'is_available' => $option->is_available,
                        ];
                    })->toArray();
                }
                return [];
            }, []),
            'additionalOptions' => $this->whenLoaded('optionGroups', function () {
                if ($this->optionGroups && $this->optionGroups->count() > 0) {
                    return $this->optionGroups->where('type', '!=', 'size')->map(function ($group) {
                        return [
                            'id' => $group->id,
                            'name' => $group->name,
                            'type' => $group->type,
                            'is_required' => $group->is_required,
                            'min_selections' => $group->min_selections,
                            'max_selections' => $group->max_selections,
                            'sort_order' => $group->sort_order,
                            'options' => $group->options && $group->options->count() > 0
                                ? $group->options->map(function ($option) {
                                    return [
                                        'id' => $option->id,
                                        'name' => $option->name,
                                        'price_modifier' => (float) $option->price_modifier,
                                        'is_available' => $option->is_available,
                                        'sort_order' => $option->sort_order,
                                    ];
                                })->toArray()
                                : [],
                        ];
                    })->values()->toArray();
                }
                return [];
            }, []),

            // Background/Cover
            'background_type' => $this->background_type,
            'background_value' => $this->background_value,

            // Metadata
            'sort_order' => $this->sort_order,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
