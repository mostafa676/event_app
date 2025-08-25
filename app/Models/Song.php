<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Song extends Model
{
    use HasFactory;

    protected $fillable = ['hall_owner_id', 'title', 'artist', 'language'];

    public function hallOwner()
    {
        return $this->belongsTo(User::class, 'hall_owner_id');
    }
}
