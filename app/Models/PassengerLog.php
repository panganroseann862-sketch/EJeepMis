<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PassengerLog extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'trip_id',
        'stop_id',
        'passenger_count',
        'boarding_count',
        'alighting_count',
        'is_over_capacity',
        'recorded_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'recorded_at' => 'datetime',
            'is_over_capacity' => 'boolean',
        ];
    }

    /**
     * Get the trip that owns the passenger log.
     */
    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }

    /**
     * Get the stop that owns the passenger log.
     */
    public function stop(): BelongsTo
    {
        return $this->belongsTo(Stop::class);
    }
}
