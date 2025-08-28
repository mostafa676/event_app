<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Notification extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = [
        'user_id','type','notifiable_id','notifiable_type',
        'title','data','is_sent_to_firebase','read_at'
    ];

    protected $casts = [
        'data' => 'array',
        'is_sent_to_firebase' => 'boolean',
        'read_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = (string) Str::uuid();
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function notifiable()
    {
        return $this->morphTo();
    }
}

