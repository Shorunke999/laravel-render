<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'artwork' => new ArtworkResource($this->whenLoaded('artwork')),
            'quantity' => $this->quantity,
            'item_price' => $this->calculateItemPrice(),
            'total_item_price' => $this->calculateTotalPrice(),
        ];
    }

    private function calculateItemPrice(): float
    {
        return $this->artwork->base_price
            + ($this->colorVariant->price_increment ?? 0)
            + ($this->sizeVariant->price_increment ?? 0);
    }
}
