<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'hall_id', 'event_type_id',
        'total_price', 'event_date'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function hall()
    {
        return $this->belongsTo(Hall::class);
    }

    public function eventType()
    {
        return $this->belongsTo(EventType::class);
    }

    public function serviceDetails()
    {
        return $this->hasMany(BookingServiceDetail::class);
    }
}
