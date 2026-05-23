<?php

namespace Database\Factories;

use App\Models\Stop;
use App\Models\Trip;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PassengerLog>
 */
class PassengerLogFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $boardingCount = fake()->numberBetween(0, 10);
        $alightingCount = fake()->numberBetween(0, 5);
        $passengerCount = fake()->numberBetween(5, 25);
        
        return [
            'trip_id' => Trip::factory(),
            'stop_id' => Stop::factory(),
            'passenger_count' => $passengerCount,
            'boarding_count' => $boardingCount,
            'alighting_count' => $alightingCount,
            'is_over_capacity' => false,
            'recorded_at' => fake()->dateTimeBetween('-2 hours', 'now'),
        ];
    }

    /**
     * Indicate that the passenger count is over capacity.
     */
    public function overCapacity(): static
    {
        return $this->state(function (array $attributes) {
            $trip = Trip::find($attributes['trip_id']);
            $capacity = $trip ? $trip->ejeep->passenger_capacity : 20;
            
            return [
                'passenger_count' => $capacity + fake()->numberBetween(1, 5),
                'is_over_capacity' => true,
            ];
        });
    }

    /**
     * Set the passenger log for a specific trip.
     */
    public function forTrip(Trip $trip): static
    {
        return $this->state(fn (array $attributes) => [
            'trip_id' => $trip->id,
        ]);
    }

    /**
     * Set the passenger log for a specific stop.
     */
    public function atStop(Stop $stop): static
    {
        return $this->state(fn (array $attributes) => [
            'stop_id' => $stop->id,
        ]);
    }

    /**
     * Set specific passenger counts.
     */
    public function withCounts(int $passengerCount, int $boardingCount, int $alightingCount): static
    {
        return $this->state(fn (array $attributes) => [
            'passenger_count' => $passengerCount,
            'boarding_count' => $boardingCount,
            'alighting_count' => $alightingCount,
        ]);
    }
}
