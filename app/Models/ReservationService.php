<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReservationService extends Model
{
    protected $fillable = [
        'reservation_id',
        'service_id',
        'service_category_id',
        'coordinator_id',
        'quantity',
        'unit_price',
        'song_id',
        'custom_song_title',
        'custom_song_artist',
        'location',
        'color',

    ];

    public function reservation()
    {
        return $this->belongsTo(Reservation::class);
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function variant()
    {
        return $this->belongsTo(ServiceVariant::class, 'service_variant_id');
    }
    public function coordinator()
    {
        return $this->belongsTo(Coordinator::class);
    }

    public function song()
    {
        return $this->belongsTo(Song::class);
    }
    
}
