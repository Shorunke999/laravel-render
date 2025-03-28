<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartResource extends JsonResource
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
            'artwork' => new ArtworkResource($this->whenLoaded('artwork')),
            'quantity' => $this->quantity,
            'color_variant' => $this->whenLoaded('colorVariants', function () {
                return [
                    'id' => $this->colorVariants->id ?? null,
                    'color' => $this->colorVariants->color ?? null,
                    'price_increment' => $this->colorVariants->price_increment ?? 0,
                ];
            }),
            'size_variant' => $this->whenLoaded('sizeVariants', function () {
                return [
                    'id' => $this->sizeVariants->id ?? null,
                    'size' => $this->sizeVariants->size ?? null,
                    'price_increment' => $this->sizeVariants->price_increment ?? 0,
                ];
            }),
            'item_price' => $this->calculateItemPrice(),
            'total_item_price' => $this->calculateTotalPrice(),
        ];
    }

    /**
     * Calculate the price of the item including variants.
     */
    private function calculateItemPrice(): float
    {
        return $this->artwork->base_price
            + optional($this->colorVariants)->price_increment ?? 0
            + optional($this->sizeVariants)->price_increment ?? 0;
    }
}
