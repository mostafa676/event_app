<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Hall extends Model
{
    use HasFactory;

    protected $fillable = [
        'name_ar', 'name_en', 'location_ar', 'location_en',
        'capacity', 'price', 'event_type_id'
    ];

    public function eventType()
    {
        return $this->belongsTo(EventType::class);
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }
    public function services()
{
    return $this->belongsToMany(Service::class, 'hall_service');
}

}
