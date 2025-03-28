<?php

namespace App\Http\Resources;

use App\Models\Artwork;
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
            'name' => $this->name,
            'description' => $this->description,
            'image_url' => $this->image_url,
            'updated_at' => $this->updated_at,
            'artworks' => $this->whenLoaded('artworks', function () {
                return ArtworkResource::collection($this->artworks);
            }),
            'artworks_count' => $this->whenLoaded('artworks', function () {
                return $this->artworks->count();
            }),
        ];;
    }
}
