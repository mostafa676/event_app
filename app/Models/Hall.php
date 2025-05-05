<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Hall extends Model
{
    use HasFactory;

    protected $fillable = [
        'name_ar', 'name_en', 'location_ar', 'location_en',
        'capacity', 'price', 'event_type_id' , 'image'
    ];

    public function eventType()
    {
        return $this->belongsTo(EventType::class);
    }

    public function services()
{
    return $this->belongsToMany(Service::class, 'hall_service');
}

protected $appends = ['image_url'];

public function getImageUrlAttribute()
{
    return $this->image ? asset('storage/' . $this->image) : null;
}

public function schedules()
{
    return $this->hasMany(HallSchedule::class);
}


}
