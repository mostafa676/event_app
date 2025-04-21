<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EventType extends Model
{
    use HasFactory;

    protected $fillable = ['name_ar', 'name_en'];

    public function halls()
    {
        return $this->hasMany(Hall::class);
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }
}
