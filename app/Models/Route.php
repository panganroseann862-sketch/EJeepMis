<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;

class Route extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'route_name',
        'route_code',
        'description',
        'status',
    ];

    /**
     * Get the stops for the route.
     */
    public function stops(): HasMany
    {
        return $this->hasMany(Stop::class);
    }

    /**
     * Get the schedules for the route.
     */
    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class);
    }

    /**
     * Get the trips for the route.
     */
    public function trips(): HasMany
    {
        return $this->hasMany(Trip::class);
    }

    /**
     * Scope a query to only include active routes.
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('status', 'active');
    }

    /**
     * Get the stops ordered by sequence.
     */
    public function getOrderedStops(): Collection
    {
        return $this->stops()->orderBy('sequence_order')->get();
    }
}
