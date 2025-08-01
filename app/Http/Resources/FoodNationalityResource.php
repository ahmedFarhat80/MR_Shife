<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Helpers\TranslationHelper;

class FoodNationalityResource extends JsonResource
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
            'name' => TranslationHelper::formatTranslatable($this, 'name'),
            'description' => $this->when($this->description, 
                TranslationHelper::formatTranslatable($this, 'description')
            ),
            'icon' => $this->icon,
            'icon_url' => $this->icon_url,
            'is_active' => $this->is_active,
            'sort_order' => $this->sort_order,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
