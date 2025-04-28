<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cart extends Model
{
    protected $fillable = [
        'user_id', 
        'event_type_id', 
        'hall_id',
        'reservation_date', 
        'start_time', 
        'end_time'
    ];

    public function services()
    {
        return $this->hasMany(CartServiceItem::class);
    }

    public function hall()
    {
        return $this->belongsTo(Hall::class);
    }

    public function event()
    {
        return $this->belongsTo(EventType::class, 'event_type_id');
    }
}


