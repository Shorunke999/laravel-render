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

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function artwork()
    {
        return $this->belongsTo(Artwork::class);
    }

    public function colorVariants()
    {
        return $this->belongsToMany(ArtworkColorVariant::class, 'cart_color_variant', 'cart_id', 'color_variant_id');
    }

    public function sizeVariants()
    {
        return $this->belongsToMany(ArtworkSizeVariant::class, 'cart_size_variant', 'cart_id', 'size_variant_id');
    }
      /**
     * Calculate the total price for this cart item
     */
    public function calculateTotalPrice(): float
    {
        $basePrice = $this->artwork->base_price;

        $colorVariantIncrement = $this->colorVariants->sum('price_increment');
        $sizeVariantIncrement = $this->sizeVariants->sum('price_increment');

        $itemPrice = $basePrice + $colorVariantIncrement + $sizeVariantIncrement;

        return $itemPrice * $this->quantity;
    }
}
