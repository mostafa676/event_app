<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DiscountCode extends Model
{
    protected $fillable = [
        'code', 'type', 'value', 'valid_from',
        'valid_to', 'max_uses', 'is_active'
    ];

    protected $casts = [
        'valid_from' => 'date',
        'valid_to' => 'date',
    ];

    public function reservations()
    {
        return $this->hasMany(Reservation::class);
    }

    public function isValid()
    {
        return $this->is_active &&
               now()->between($this->valid_from, $this->valid_to) &&
               ($this->max_uses === null || $this->used_count < $this->max_uses);
    }
}