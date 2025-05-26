<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubscriptionPlanResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Get current locale from app
        $locale = app()->getLocale();

        return [
            'id' => $this->id,
            'name' => $this->getTranslation('name', $locale) ?: $this->getTranslation('name', 'en'),
            'description' => $this->getTranslation('description', $locale) ?: $this->getTranslation('description', 'en'),
            'price' => $this->price,
            'formatted_price' => $this->getFormattedPrice(),
            'period' => $this->period,
            'period_label' => $this->getPeriodLabel(),
            'features' => $this->getTranslation('features', $locale) ?: $this->getTranslation('features', 'en') ?: [],
            'is_active' => $this->is_active,
            'is_popular' => $this->is_popular,
            'is_free' => $this->isFree(),
            'sort_order' => $this->sort_order,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
