<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CoordinatorType extends Model
{
    protected $fillable = ['name_ar', 'name_en'];

    public function coordinators()
    {
        return $this->hasMany(Coordinator::class);
    }
    
}