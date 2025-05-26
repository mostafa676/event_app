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
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }
}