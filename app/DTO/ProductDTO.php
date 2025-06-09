<?php

namespace App\DTO;

class ProductDTO
{
    public function __construct(
        public readonly int $merchantId,
        public readonly ?int $internalCategoryId,
        public readonly ?int $foodNationalityId,
        public readonly array $name,
        public readonly ?array $description,
        public readonly string $backgroundType,
        public readonly ?string $backgroundValue,
        public readonly float $basePrice,
        public readonly ?float $discountPercentage,
        public readonly bool $isAvailable,
        public readonly int $preparationTime,
        public readonly ?string $sku,
        public readonly ?int $calories,
        public readonly ?array $ingredients,
        public readonly ?array $allergens,
        public readonly bool $isVegetarian,
        public readonly bool $isVegan,
        public readonly bool $isGlutenFree,
        public readonly bool $isSpicy,
        public readonly bool $isFeatured,
        public readonly int $sortOrder,
        public readonly int $stockQuantity,
        public readonly bool $trackStock,
    ) {}

    /**
     * Create DTO from request data.
     */
    public static function fromRequest(array $data, int $merchantId): self
    {
        return new self(
            merchantId: $merchantId,
            internalCategoryId: $data['internal_category_id'] ?? null,
            foodNationalityId: $data['food_nationality_id'] ?? null,
            name: [
                'en' => $data['name_en'],
                'ar' => $data['name_ar'] ?? $data['name_en'],
            ],
            description: isset($data['description_en']) ? [
                'en' => $data['description_en'],
                'ar' => $data['description_ar'] ?? $data['description_en'],
            ] : null,
            backgroundType: $data['background_type'] ?? 'color',
            backgroundValue: $data['background_value'] ?? null,
            basePrice: (float) $data['base_price'],
            discountPercentage: isset($data['discount_percentage']) ? (float) $data['discount_percentage'] : null,
            isAvailable: (bool) ($data['is_available'] ?? true),
            preparationTime: (int) ($data['preparation_time'] ?? 15),
            sku: $data['sku'] ?? null,
            calories: isset($data['calories']) ? (int) $data['calories'] : null,
            ingredients: isset($data['ingredients']) ? (array) $data['ingredients'] : null,
            allergens: isset($data['allergens']) ? (array) $data['allergens'] : null,
            isVegetarian: (bool) ($data['is_vegetarian'] ?? false),
            isVegan: (bool) ($data['is_vegan'] ?? false),
            isGlutenFree: (bool) ($data['is_gluten_free'] ?? false),
            isSpicy: (bool) ($data['is_spicy'] ?? false),
            isFeatured: (bool) ($data['is_featured'] ?? false),
            sortOrder: (int) ($data['sort_order'] ?? 0),
            stockQuantity: (int) ($data['stock_quantity'] ?? 0),
            trackStock: (bool) ($data['track_stock'] ?? false),
        );
    }

    /**
     * Convert DTO to array for model creation/update.
     */
    public function toArray(): array
    {
        return [
            'merchant_id' => $this->merchantId,
            'internal_category_id' => $this->internalCategoryId,
            'food_nationality_id' => $this->foodNationalityId,
            'name' => $this->name,
            'description' => $this->description,
            'background_type' => $this->backgroundType,
            'background_value' => $this->backgroundValue,
            'base_price' => $this->basePrice,
            'discount_percentage' => $this->discountPercentage,
            'is_available' => $this->isAvailable,
            'preparation_time' => $this->preparationTime,
            'sku' => $this->sku,
            'calories' => $this->calories,
            'ingredients' => $this->ingredients,
            'allergens' => $this->allergens,
            'is_vegetarian' => $this->isVegetarian,
            'is_vegan' => $this->isVegan,
            'is_gluten_free' => $this->isGlutenFree,
            'is_spicy' => $this->isSpicy,
            'is_featured' => $this->isFeatured,
            'sort_order' => $this->sortOrder,
            'stock_quantity' => $this->stockQuantity,
            'track_stock' => $this->trackStock,
        ];
    }

    /**
     * Calculate discounted price.
     */
    public function calculateDiscountedPrice(): ?float
    {
        if ($this->discountPercentage && $this->discountPercentage > 0) {
            $discountAmount = ($this->basePrice * $this->discountPercentage) / 100;
            return $this->basePrice - $discountAmount;
        }
        return null;
    }
}
