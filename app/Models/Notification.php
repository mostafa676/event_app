<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Notification extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';
    protected $table = 'notifications'; // تأكد من اسم الجدول

    protected $fillable = [
        'id',
        'user_id',
        'type',
        'notifiable_id',
        'notifiable_type',
        'title',
        'data',
        'read_at',
        'is_sent_to_firebase',
    ];

    protected $casts = [
        'data' => 'array', // ⚠️ مهم: لا تستخدم json_encode يدويًا
        'read_at' => 'datetime',
        'is_sent_to_firebase' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
            // تأكد أن is_sent_to_firebase له قيمة افتراضية
            if (!isset($model->is_sent_to_firebase)) {
                $model->is_sent_to_firebase = false;
            }
        });
    }

    // علاقة المستخدم
    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    // علاقة notifiable (مثلاً Reservation)
    public function notifiable()
    {
        return $this->morphTo();
    }
}