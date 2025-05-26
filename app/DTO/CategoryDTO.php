<?php

namespace App\DTO;

class CategoryDTO
{
    public function __construct(
        public readonly int $merchantId,
        public readonly array $name,
        public readonly ?array $description,
        public readonly ?string $image,
        public readonly int $sortOrder,
        public readonly bool $isActive,
    ) {}

    /**
     * Create DTO from request data.
     */
    public static function fromRequest(array $data, int $merchantId): self
    {
        return new self(
            merchantId: $merchantId,
            name: [
                'en' => $data['name_en'],
                'ar' => $data['name_ar'] ?? $data['name_en'],
            ],
            description: isset($data['description_en']) ? [
                'en' => $data['description_en'],
                'ar' => $data['description_ar'] ?? $data['description_en'],
            ] : null,
            image: $data['image'] ?? null,
            sortOrder: (int) ($data['sort_order'] ?? 0),
            isActive: (bool) ($data['is_active'] ?? true),
        );
    }

    /**
     * Convert DTO to array for model creation/update.
     */
    public function toArray(): array
    {
        return [
            'merchant_id' => $this->merchantId,
            'name' => $this->name,
            'description' => $this->description,
            'image' => $this->image,
            'sort_order' => $this->sortOrder,
            'is_active' => $this->isActive,
        ];
    }
}
