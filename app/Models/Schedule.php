<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Schedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'route_id',
        'ejeep_id',
        'driver_id',
        'departure_time',
        'day_of_week',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'departure_time' => 'datetime:H:i',
        ];
    }

    public function route(): BelongsTo
    {
        return $this->belongsTo(Route::class)->withTrashed();
    }

    public function ejeep(): BelongsTo
    {
        return $this->belongsTo(Ejeep::class)->withTrashed();
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    public function trips(): HasMany
    {
        return $this->hasMany(Trip::class);
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('status', 'active');
    }

    public function scopeForDay(Builder $query, string $day): void
    {
        $query->where('day_of_week', $day);
    }

    public function scopeForDriver(Builder $query, int $driverId): void
    {
        $query->where('driver_id', $driverId);
    }
}