<?php

// App\Models\Reservation.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reservation extends Model
{

    protected $table = 'reservation';
    
    protected $fillable = [
        'user_id', 'hall_id', 'event_type_id',
        'reservation_date', 'start_time', 'end_time', 'status', 'home_address',
        'total_price', 'discount_code_id', 'discount_amount' ,'coordinator_id'
    ];
    
 protected $casts = [
        'reservation_date' => 'date',
    ];

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function hall() {
        return $this->belongsTo(Hall::class);
    }

    public function eventType() {
        return $this->belongsTo(EventType::class);
    }
    public function discountCode()
    {
        return $this->belongsTo(DiscountCode::class);
    }

    public function services()
    {
        return $this->hasMany(ReservationService::class);
    }

public function reservationServices()
{
    return $this->hasMany(\App\Models\ReservationService::class, 'reservation_id');
}

public function songRequests()
    {
        return $this->hasMany(CustomSongRequest::class);
    }

}

