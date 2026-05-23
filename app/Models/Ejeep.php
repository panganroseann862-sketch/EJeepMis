<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Ejeep extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'vehicle_number',
        'plate_number',
        'passenger_capacity',
        'operational_status',
        'maintenance_notes',
        'last_maintenance_date',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'last_maintenance_date' => 'date',
        ];
    }

    /**
     * Get the schedules for the E-Jeep.
     */
    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class);
    }

    /**
     * Get the trips for the E-Jeep.
     */
    public function trips(): HasMany
    {
        return $this->hasMany(Trip::class);
    }

    /**
     * Scope a query to only include active E-Jeeps.
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('operational_status', 'active');
    }

    /**
     * Scope a query to only include available E-Jeeps.
     */
    public function scopeAvailable(Builder $query): void
    {
        $query->where('operational_status', 'active')
              ->whereDoesntHave('trips', function ($query) {
                  $query->whereIn('status', ['scheduled', 'in_progress', 'paused']);
              });
    }

    /**
     * Check if the E-Jeep is available for assignment.
     */
    public function isAvailable(): bool
    {
        return $this->operational_status === 'active' 
            && !$this->trips()->whereIn('status', ['scheduled', 'in_progress', 'paused'])->exists();
    }

    /**
     * Check if the E-Jeep needs maintenance.
     */
    public function needsMaintenance(): bool
    {
        return $this->operational_status === 'maintenance';
    }
}
