<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Trip extends Model
{
    use HasFactory;

    protected $fillable = [
        'schedule_id',
        'ejeep_id',
        'driver_id',
        'route_id',
        'status',
        'scheduled_start_time',
        'actual_start_time',
        'actual_end_time',
        'current_passenger_count',
        'max_passenger_count',
        'has_route_deviation',
        'deviation_notes',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_start_time' => 'datetime',
            'actual_start_time' => 'datetime',
            'actual_end_time' => 'datetime',
            'has_route_deviation' => 'boolean',
        ];
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(Schedule::class);
    }

    public function ejeep(): BelongsTo
    {
        return $this->belongsTo(Ejeep::class)->withTrashed();
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    public function route(): BelongsTo
    {
        return $this->belongsTo(Route::class)->withTrashed();
    }

    public function passengerLogs(): HasMany
    {
        return $this->hasMany(PassengerLog::class);
    }

    public function scopeActive(Builder $query): void
    {
        $query->whereIn('status', ['scheduled', 'in_progress', 'paused']);
    }

    public function scopeInProgress(Builder $query): void
    {
        $query->where('status', 'in_progress');
    }

    public function scopeCompleted(Builder $query): void
    {
        $query->where('status', 'completed');
    }

    public function scopeForDriver(Builder $query, int $driverId): void
    {
        $query->where('driver_id', $driverId);
    }

    public function isOverCapacity(): bool
    {
        return $this->current_passenger_count > ($this->ejeep->capacity ?? 0);
    }

    public function getRemainingCapacity(): int
    {
        return max(0, ($this->ejeep->capacity ?? 0) - $this->current_passenger_count);
    }

    public function getDuration(): ?int
    {
        if ($this->actual_start_time && $this->actual_end_time) {
            return $this->actual_start_time->diffInMinutes($this->actual_end_time);
        }

        return null;
    }
}