<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    use HasFactory;

    protected $fillable = ['name_ar', 'name_en', 'avg_price'];


    public function halls()
{
    return $this->belongsToMany(Hall::class, 'hall_service');
}
public function variants()
{
    return $this->hasMany(ServiceVariant::class);
}
protected $appends = ['image_url'];

public function getImageUrlAttribute()
{
    return $this->image ? asset('storage/' . $this->image) : null;
}

}
