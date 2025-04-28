<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HallSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'hall_id', 'day_of_week', 'start_time', 'end_time', 'is_available',
    ];

    public function hall()
    {
        return $this->belongsTo(Hall::class);
    }
}
