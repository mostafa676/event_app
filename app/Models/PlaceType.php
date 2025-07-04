<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlaceType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name_ar',
        'name_en',
    ];

    /**
     * العلاقة مع الصالات (halls)
     */
    public function halls()
    {
        return $this->hasMany(Hall::class);
    }
}
