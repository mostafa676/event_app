<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceVariant extends Model
{
    protected $fillable = ['service_id', 'name_ar', 'name_en', 'color', 'description','price' , 'image'];

    public function service()
    {
        return $this->belongsTo(Service::class);
    }
    public function reservationServices()
    {
        return $this->hasMany(ReservationService::class);
    }
    protected $appends = ['image_url'];

public function getImageUrlAttribute()
{
    return $this->image ? asset('storage/' . $this->image) : null;
}

}

