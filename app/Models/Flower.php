<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Flower extends Model
{
    use HasFactory;

    protected $fillable = ['decoration_type_id', 'name_ar', 'name_en','color' ,'price'];

    public function decorationType()
    {
        return $this->belongsTo(DecorationType::class);
    }

}
