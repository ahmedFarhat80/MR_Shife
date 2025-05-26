<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
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
            'name' => [
                'en' => $this->getTranslation('name', 'en'),
                'ar' => $this->getTranslation('name', 'ar'),
                'current' => $this->name,
            ],
            'description' => $this->when($this->description, [
                'en' => $this->getTranslation('description', 'en'),
                'ar' => $this->getTranslation('description', 'ar'),
                'current' => $this->description,
            ]),
            'image' => $this->image_url,
            'sort_order' => $this->sort_order,
            'is_active' => $this->is_active,
            'products_count' => $this->when(isset($this->products_count), $this->products_count),
            'active_products_count' => $this->when(isset($this->active_products_count), $this->active_products_count),
            'products' => ProductResource::collection($this->whenLoaded('products')),
            'active_products' => ProductResource::collection($this->whenLoaded('activeProducts')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
