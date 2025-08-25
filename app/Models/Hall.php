<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Hall extends Model
{
    use HasFactory;

    protected $fillable = [
         'user_id', 'event_type_id', 'name_ar', 'name_en',
        'location_ar', 'location_en', 'capacity', 'price',
        'image_1', 'image_2', 'image_3', 'image_4', 'image_5', 'image_6','place_type_id'
    ];

    public function owner()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function eventType()
    {
        return $this->belongsTo(EventType::class);
    }

    public function services()
    {
        return $this->belongsToMany(Service::class, 'hall_service');
    }

    public function ratings()
{
    return $this->hasMany(HallRating::class);
}

    public function schedules()
    {
        return $this->hasMany(HallSchedule::class);
    }

    public function reservations()
    {
        return $this->hasMany(Reservation::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function coordinators()
    {
        return $this->hasManyThrough(
            Coordinator::class,
            User::class,
            'id', 
            'hall_owner_id',
            'user_id',
            'id'
        );
    }
    
public function statistics()
{
    return $this->hasMany(Statistic::class);
}

public function getPopularityAttribute()
{
    return $this->statistics()
        ->where('metric_type', 'popular_hall')
        ->sum('count');
}
protected $appends = ['image_url'];

    public function getImageUrlAttribute()
    {
         $urls = [];
        for ($i = 1; $i <= 6; $i++) {
            $field = "image_$i";
            if ($this->$field) {
                $urls[$field] = Storage::url($this->$field);
            }
        }
        return $urls;
    }

public function placeType()
{
    return $this->belongsTo(PlaceType::class);
}


}
