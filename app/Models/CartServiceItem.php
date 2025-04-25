<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CartServiceItem extends Model
{
    protected $fillable = ['cart_id', 'service_id', 'service_variant_id', 'quantity', 'unit_price'];

    public function cart()
    {
        return $this->belongsTo(Cart::class);
    }

    public function variant()
    {
        return $this->belongsTo(ServiceVariant::class, 'service_variant_id');
    }
}
