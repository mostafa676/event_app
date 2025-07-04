<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CoordinatorPortfolio extends Model
{
    use HasFactory;

    protected $fillable = ['coordinator_id', 'image', 'camera_info', 'description'];

    public function coordinator()
    {
        return $this->belongsTo(Coordinator::class);
    }
}

