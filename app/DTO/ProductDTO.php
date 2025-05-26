<?php

namespace App\DTO;

class ProductDTO
{
    public function __construct(
        public readonly int $merchantId,
        public readonly int $categoryId,
        public readonly array $name,
        public readonly ?array $description,
        public readonly float $price,
        public readonly ?float $discountPrice,
        public readonly ?string $sku,
        public readonly ?array $images,
        public readonly int $preparationTime,
        public readonly ?int $calories,
        public readonly ?array $ingredients,
        public readonly ?array $allergens,
        public readonly bool $isVegetarian,
        public readonly bool $isVegan,
        public readonly bool $isGlutenFree,
        public readonly bool $isSpicy,
        public readonly bool $isAvailable,
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
            categoryId: $data['category_id'],
            name: [
                'en' => $data['name_en'],
                'ar' => $data['name_ar'] ?? $data['name_en'],
            ],
            description: isset($data['description_en']) ? [
                'en' => $data['description_en'],
                'ar' => $data['description_ar'] ?? $data['description_en'],
            ] : null,
            price: (float) $data['price'],
            discountPrice: isset($data['discount_price']) ? (float) $data['discount_price'] : null,
            sku: $data['sku'] ?? null,
            images: $data['images'] ?? null,
            preparationTime: (int) ($data['preparation_time'] ?? 15),
            calories: isset($data['calories']) ? (int) $data['calories'] : null,
            ingredients: isset($data['ingredients_en']) ? [
                'en' => $data['ingredients_en'],
                'ar' => $data['ingredients_ar'] ?? $data['ingredients_en'],
            ] : null,
            allergens: isset($data['allergens_en']) ? [
                'en' => $data['allergens_en'],
                'ar' => $data['allergens_ar'] ?? $data['allergens_en'],
            ] : null,
            isVegetarian: (bool) ($data['is_vegetarian'] ?? false),
            isVegan: (bool) ($data['is_vegan'] ?? false),
            isGlutenFree: (bool) ($data['is_gluten_free'] ?? false),
            isSpicy: (bool) ($data['is_spicy'] ?? false),
            isAvailable: (bool) ($data['is_available'] ?? true),
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
            'category_id' => $this->categoryId,
            'name' => $this->name,
            'description' => $this->description,
            'price' => $this->price,
            'discount_price' => $this->discountPrice,
            'sku' => $this->sku,
            'images' => $this->images,
            'preparation_time' => $this->preparationTime,
            'calories' => $this->calories,
            'ingredients' => $this->ingredients,
            'allergens' => $this->allergens,
            'is_vegetarian' => $this->isVegetarian,
            'is_vegan' => $this->isVegan,
            'is_gluten_free' => $this->isGlutenFree,
            'is_spicy' => $this->isSpicy,
            'is_available' => $this->isAvailable,
            'is_featured' => $this->isFeatured,
            'sort_order' => $this->sortOrder,
            'stock_quantity' => $this->stockQuantity,
            'track_stock' => $this->trackStock,
        ];
    }
}
