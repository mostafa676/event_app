<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceCategory extends Model
{
    use HasFactory;

    protected $fillable = ['service_id', 'name_ar','image_1', 'name_en'];

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

//     public function types()
// {
//     return $this->hasMany(ServiceType::class, 'service_id'); 
// }


public function variants()
{
    return $this->hasMany(ServiceVariant::class, 'service_category_id');
}

}
