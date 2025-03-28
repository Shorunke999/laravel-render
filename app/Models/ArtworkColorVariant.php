<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ArtworkColorVariant extends Model
{

    protected $fillable = ['artwork_id', 'color', 'price_increment', 'stock'];

    public function artwork()
    {
        return $this->belongsTo(Artwork::class);
    }

    public function getFinalPriceAttribute()
    {
        return $this->artwork->base_price + $this->price_increment;
    }
}
