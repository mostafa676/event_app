<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HallRating extends Model
{
    protected $fillable = ['hall_id', 'user_id', 'stars', 'review'];

    public function hall()
    {
        return $this->belongsTo(Hall::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

