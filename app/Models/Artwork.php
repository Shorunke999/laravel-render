<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Artwork extends Model
{
    /** @use HasFactory<\Database\Factories\ArtworkFactory> */
    use HasFactory;
    protected $fillable = ['name', 'category_id', 'artist','base_price', 'description', 'stock','cart_stock'];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function colorVariants()
    {
        return $this->hasMany(ArtworkColorVariant::class);
    }

    public function sizeVariants()
    {
        return $this->hasMany(ArtworkSizeVariant::class);
    }
    public function images()
    {
        return $this->hasMany(ArtworkImage::class);
    }

    public function getFinalPriceAttribute()
    {
        return $this->base_price;
    }

    public function averageRating()
    {
        return $this->reviews()
            ->avg('rating');
    }
}
