<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id',
        'artwork_id',
        'quantity',
        'price',
        'selected_size'
    ];

    protected $casts = [
        'price' => 'decimal:2'
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function artwork()
    {
        return $this->belongsTo(Artwork::class);
    }

}
