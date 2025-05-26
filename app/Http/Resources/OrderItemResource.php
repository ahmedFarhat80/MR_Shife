<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderItemResource extends JsonResource
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
            'product_id' => $this->product_id,
            'product' => new ProductResource($this->whenLoaded('product')),
            'product_snapshot' => [
                'name' => $this->product_name,
                'image' => $this->product_image,
                'details' => $this->product_snapshot,
            ],
            'quantity' => $this->quantity,
            'pricing' => [
                'unit_price' => [
                    'amount' => (float) $this->unit_price,
                    'formatted' => number_format($this->unit_price, 2),
                ],
                'total_price' => [
                    'amount' => (float) $this->total_price,
                    'formatted' => number_format($this->total_price, 2),
                ],
                'currency' => 'USD',
            ],
            'customizations' => $this->customizations,
            'special_instructions' => $this->special_instructions,
        ];
    }
}
