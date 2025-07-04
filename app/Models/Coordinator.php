<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Coordinator extends Model
{
    protected $fillable = [
        'user_id', 'coordinator_type_id', 'hall_owner_id',
        'is_active', 'hourly_rate'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function type()
    {
        return $this->belongsTo(CoordinatorType::class, 'coordinator_type_id');
    }

    public function hallOwner()
    {
        return $this->belongsTo(User::class, 'hall_owner_id');
    }

    public function coordinatorAssignments()
    {
        return $this->hasMany(CoordinatorAssignment::class, 'coordinator_id');
    }

      public function reservations()
    {
        return $this->hasMany(Reservation::class, 'coordinator_id');
    }
    public function portfolios()
    {
        return $this->hasMany(CoordinatorPortfolio::class);
    }
}