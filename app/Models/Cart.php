<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cart extends Model
{
    protected $fillable = [
        'user_id',
        'artwork_id',
        'quantity',
        'color_variant_id',
        'size_variant_id'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function artwork()
    {
        return $this->belongsTo(Artwork::class);
    }


    public function calculateTotalPrice(): float
    {
        $basePrice = $this->artwork->base_price;
        $colorIncrement = $this->colorVariant->price_increment ?? 0;
        $sizeIncrement = $this->sizeVariant->price_increment ?? 0;

        return ($basePrice + $colorIncrement + $sizeIncrement) * $this->quantity;
    }
}
