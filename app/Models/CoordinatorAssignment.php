<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CoordinatorAssignment extends Model
{
    protected $fillable = [
        'reservation_id',
        'service_id',
        'coordinator_id',
        'assigned_by',
        'status',
        'instructions',
        'completed_at'
    ];

    protected $casts = [
        'completed_at' => 'datetime'
    ];

    public function reservation()
    {
        return $this->belongsTo(Reservation::class);
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function coordinator()
    {
        return $this->belongsTo(Coordinator::class);
    }

    public function assigner()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    // حالة المهمة
    public function scopeworking_on($query)
    {
        return $query->where('status', 'working_on');
    }

    public function scopeaccepted($query)
    {
        return $query->where('status', 'accepted');
    }
    public function scoperejected($query)
    {
        return $query->where('status', 'rejected');
    }
}