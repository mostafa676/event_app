<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    use HasFactory;

    protected $fillable = ['name_ar', 'name_en'];


    public function halls()
{
    return $this->belongsToMany(Hall::class, 'hall_service');
}
public function variants()
{
    return $this->hasMany(ServiceVariant::class);

}
public function reservationServices()
    {
        return $this->hasMany(ReservationService::class);
    }
public function coordinators()
    {
        return $this->belongsToMany(Coordinator::class, 'service_coordinator', 'service_id', 'coordinator_id');
    }

     public function categories()
    {
        return $this->hasMany(ServiceCategory::class);
    }
}
