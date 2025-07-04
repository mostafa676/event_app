<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceType extends Model
{
    use HasFactory;

    protected $fillable = ['service_variant_id', 'name_ar', 'name_en', 'price', 'description', 'image'];

    public function category()
    {
        return $this->belongsTo(ServiceCategory::class);
    }
    public function variant()
    {
    return $this->belongsTo(ServiceVariant::class, 'service_variant_id');
    }
       
}