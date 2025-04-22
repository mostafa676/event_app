<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BookingServiceDetail extends Model
{
    use HasFactory;

    protected $fillable = [
    'booking_id', 'variant_id',
        'quantity', 'total_price'
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function serviceVariant()
{
    return $this->belongsTo(ServiceVariant::class, 'variant_id');
}

}
