<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentTransaction extends Model
{
    protected $fillable = [
        'order_id',
        'amount',
        'reference',
        'status'
    ];
    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
