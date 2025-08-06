<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DecorationType extends Model
{
    use HasFactory;

    protected $fillable = ['service_id', 'name_ar','image_1', 'name_en'];

    public function flowers()
    {
        return $this->hasMany(Flower::class);
    }
        public function service()
    {
        return $this->belongsTo(Service::class);
    }

}
