<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Stop extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'route_id',
        'stop_name',
        'location_description',
        'latitude',
        'longitude',
        'sequence_order',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:8',
            'longitude' => 'decimal:8',
        ];
    }

    /**
     * Get the route that owns the stop.
     */
    public function route(): BelongsTo
    {
        return $this->belongsTo(Route::class);
    }

    /**
     * Get the passenger logs for the stop.
     */
    public function passengerLogs(): HasMany
    {
        return $this->hasMany(PassengerLog::class);
    }
}
