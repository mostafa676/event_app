<?php

// app/Models/Supervisor.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Supervisor extends Model
{
    protected $fillable = ['name', 'phone', 'is_available'];

    public function reservations()
    {
        return $this->hasMany(Reservation::class);
    }
}

