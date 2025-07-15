<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceVariant extends Model
{
    protected $fillable = ['service_category_id', 'name_ar', 'name_en', 'image'];

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
public function category()
{
    return $this->belongsTo(ServiceCategory::class, 'service_category_id');
}

public function types()
{
    return $this->hasMany(ServiceType::class, 'service_variant_id');
}

}

