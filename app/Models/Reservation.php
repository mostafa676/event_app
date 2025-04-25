<?php

// App\Models\Reservation.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reservation extends Model
{

    protected $table = 'reservation';
    
    protected $fillable = [
        'user_id', 'hall_id', 'service_id', 'event_id',
        'reservation_date', 'status'
    ];

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function hall() {
        return $this->belongsTo(Hall::class);
    }

    public function service() {
        return $this->belongsTo(Service::class);
    }

    public function event() {
        return $this->belongsTo(EventType::class);
    }
    public function services()
    {
        return $this->hasMany(ReservationService::class);
    }

}

