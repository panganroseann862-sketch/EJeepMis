<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'username',
        'email',
        'password',
        'role',
        'first_name',
        'last_name',
        'phone',
        'status',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get the name of the unique identifier for the user.
     *
     * @return string
     */
    public function getAuthIdentifierName(): string
    {
        return 'username';
    }

    /**
     * Get the schedules assigned to the driver.
     */
    public function driverSchedules(): HasMany
    {
        return $this->hasMany(Schedule::class, 'driver_id');
    }

    /**
     * Get the trips assigned to the driver.
     */
    public function driverTrips(): HasMany
    {
        return $this->hasMany(Trip::class, 'driver_id');
    }

    /**
     * Get the notifications for the user.
     */
    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    /**
     * Scope a query to only include admin users.
     */
    public function scopeAdmins(Builder $query): void
    {
        $query->where('role', 'admin');
    }

    /**
     * Scope a query to only include driver users.
     */
    public function scopeDrivers(Builder $query): void
    {
        $query->where('role', 'driver');
    }

    /**
     * Scope a query to only include student users.
     */
    public function scopeStudents(Builder $query): void
    {
        $query->where('role', 'student');
    }

    /**
     * Scope a query to only include active users.
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('status', 'active');
    }

    /**
     * Check if the user is an admin.
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Check if the user is a driver.
     */
    public function isDriver(): bool
    {
        return $this->role === 'driver';
    }

    /**
     * Check if the user is a student.
     */
    public function isStudent(): bool
    {
        return $this->role === 'student';
    }
}