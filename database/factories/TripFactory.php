<?php

namespace Database\Factories;

use App\Models\Ejeep;
use App\Models\Route;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Trip>
 */
class TripFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'schedule_id' => Schedule::factory(),
            'ejeep_id' => Ejeep::factory(),
            'driver_id' => User::factory()->driver(),
            'route_id' => Route::factory(),
            'status' => 'scheduled',
            'scheduled_start_time' => fake()->dateTimeBetween('now', '+2 hours'),
            'actual_start_time' => null,
            'actual_end_time' => null,
            'current_passenger_count' => 0,
            'max_passenger_count' => 0,
            'has_route_deviation' => false,
            'deviation_notes' => null,
        ];
    }

    /**
     * Indicate that the trip is in progress.
     */
    public function inProgress(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'in_progress',
            'actual_start_time' => fake()->dateTimeBetween('-2 hours', 'now'),
            'current_passenger_count' => fake()->numberBetween(5, 20),
            'max_passenger_count' => fake()->numberBetween(5, 25),
        ]);
    }

    /**
     * Indicate that the trip is paused.
     */
    public function paused(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'paused',
            'actual_start_time' => fake()->dateTimeBetween('-3 hours', '-1 hour'),
            'current_passenger_count' => fake()->numberBetween(5, 20),
            'max_passenger_count' => fake()->numberBetween(5, 25),
        ]);
    }

    /**
     * Indicate that the trip is completed.
     */
    public function completed(): static
    {
        $startTime = fake()->dateTimeBetween('-1 week', '-1 day');
        
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'actual_start_time' => $startTime,
            'actual_end_time' => fake()->dateTimeBetween($startTime, 'now'),
            'current_passenger_count' => 0,
            'max_passenger_count' => fake()->numberBetween(10, 30),
        ]);
    }

    /**
     * Indicate that the trip is cancelled.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
        ]);
    }

    /**
     * Indicate that the trip has a route deviation.
     */
    public function withDeviation(): static
    {
        return $this->state(fn (array $attributes) => [
            'has_route_deviation' => true,
            'deviation_notes' => fake()->sentence(),
        ]);
    }

    /**
     * Set the trip to be over capacity.
     */
    public function overCapacity(): static
    {
        return $this->state(function (array $attributes) {
            $capacity = Ejeep::find($attributes['ejeep_id'])->passenger_capacity ?? 20;
            
            return [
                'current_passenger_count' => $capacity + fake()->numberBetween(1, 5),
                'max_passenger_count' => $capacity + fake()->numberBetween(1, 5),
            ];
        });
    }

    /**
     * Set the trip for a specific driver.
     */
    public function forDriver(User $driver): static
    {
        return $this->state(fn (array $attributes) => [
            'driver_id' => $driver->id,
        ]);
    }

    /**
     * Set the trip for a specific E-Jeep.
     */
    public function withEjeep(Ejeep $ejeep): static
    {
        return $this->state(fn (array $attributes) => [
            'ejeep_id' => $ejeep->id,
        ]);
    }

    /**
     * Set the trip for a specific route.
     */
    public function forRoute(Route $route): static
    {
        return $this->state(fn (array $attributes) => [
            'route_id' => $route->id,
        ]);
    }
}
