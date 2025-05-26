<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
'name', 'email', 'password', 'phone', 'password', 'role', 'image', 
        'dark_mode', 'language'
    ];
    
    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        // 'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];


    protected $appends = ['image_url'];

public function getImageUrlAttribute()
{
    return $this->image ? asset('storage/' . $this->image) : null;
}

public function searchHistories()
{
    return $this->hasMany(SearchHistory::class);
}

public function halls()
    {
        return $this->hasMany(Hall::class);
    }

    public function reservations()
    {
        return $this->hasMany(Reservation::class);
    }

    public function ownedCoordinators()
    {
        return $this->hasMany(Coordinator::class, 'hall_owner_id');
    }

    public function coordinatorProfile()
    {
        return $this->hasOne(Coordinator::class);
    }

    public function favorites()
    {
        return $this->hasMany(Favorite::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    // Scopes للصلاحيات
    public function isAdmin()
    {
        return $this->role === 'admin';
    }

    public function isHallOwner()
    {
        return $this->role === 'hall_owner';
    }

    public function isCoordinator()
    {
        return $this->role === 'coordinator';
    }

    // في ملف app/Models/User.php
public function hallStatistics()
{
    return $this->hasManyThrough(
        Statistic::class,
        Hall::class,
        'user_id', // Foreign key on halls table
        'hall_id', // Foreign key on statistics table
        'id', // Local key on users table
        'id' // Local key on halls table
    );
}

public function getCompletedReservationsCountAttribute()
{
    return $this->hallStatistics()
        ->where('metric_type', 'completed_reservations')
        ->sum('count');
}

}
