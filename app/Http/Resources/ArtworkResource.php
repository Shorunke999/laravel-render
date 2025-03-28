<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ArtworkResource extends JsonResource
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
            'name' => $this->name,
            'category' => new CategoryResource($this->whenLoaded('category')),
            'artist' => $this->artist,
            'size' => $this->size,
            'weight' => $this->weight,
            'price' => $this->price,
            'description' => $this->description,
            'image' => ArtworkimageResource::collection($this->whenLoaded('images')),
            'stock' => $this->stock,
            'color_variants' => ArtworkColorVariantResource::collection($this->whenLoaded('colorVariants')),
            'size_variants' => ArtworkSizeVariantResource::collection($this->whenLoaded('sizeVariants')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'average_rating' => $this->whenLoaded('reviews', function () {
                return $this->reviews->avg('rating');
            }),
            'reviews_count' => $this->whenLoaded('reviews', function () {
                return $this->reviews->count();
            }),
        ];
    }
}
