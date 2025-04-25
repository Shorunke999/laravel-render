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
            //'category' => $this->whenLoaded('category', function () {
              //  return new CategoryResource($this->category);
            //}),
            'artist' => $this->artist,
            'base_price' => $this->base_price,
            'description' => $this->description,
            'images' => $this->whenLoaded('images', function () {
                return ArtworkImageResource::collection($this->images);
            }),
            'stock' => $this->stock,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'average_rating' => $this->whenLoaded('reviews', function () {
                return $this->reviews->avg('rating');
            }),
            //'reviews_count' => $this->whenLoaded('reviews', function () {
               // return $this->reviews->count();
           // }),
        ];
    }
}
