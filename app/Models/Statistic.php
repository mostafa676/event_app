<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Statistic extends Model
{
    protected $fillable = [
        'hall_id',
        'user_id',
        'metric_type',
        'count',
        'record_date'
    ];

    protected $casts = [
        'record_date' => 'date'
    ];

    public function hall()
    {
        return $this->belongsTo(Hall::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // أنواع المقاييس
    const METRIC_TYPES = [
        'popular_hall' => 'الصالة الأكثر طلباً',
        'completed_reservations' => 'الحجوزات المكتملة'
    ];

    public function getMetricNameAttribute()
    {
        return self::METRIC_TYPES[$this->metric_type] ?? $this->metric_type;
    }
}