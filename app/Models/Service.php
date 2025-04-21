<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    use HasFactory;

    protected $fillable = ['name_ar', 'name_en', 'price_per_unit'];

    public function bookingDetails()
    {
        return $this->hasMany(BookingServiceDetail::class);
    }
}
