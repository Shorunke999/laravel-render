<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
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
            'total_amount' =>  $this->total_amount,
            'status'=> $this->status,
            'payment_status' => $this->payment_status,
            'contact' => $this->contact,
            'user' => $this->whenLoaded('user', function(){
                return new UserResource($this->user);
            }),
            'placed_on' => $this->created_at,
            'delivered_at'=> $this->delivered_at
        ];
    }
}
