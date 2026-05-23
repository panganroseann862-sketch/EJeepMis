<?php

namespace Database\Factories;

use App\Models\Ejeep;
use App\Models\Route;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Schedule>
 */
class ScheduleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'route_id' => Route::factory(),
            'ejeep_id' => Ejeep::factory(),
            'driver_id' => User::factory()->driver(),
            'departure_time' => fake()->time('H:i:s'),
            'day_of_week' => fake()->randomElement([
                'monday',
                'tuesday',
                'wednesday',
                'thursday',
                'friday',
                'saturday',
                'sunday',
            ]),
            'status' => 'active',
        ];
    }

    /**
     * Indicate that the schedule is cancelled.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
        ]);
    }

    /**
     * Set the schedule for a specific day.
     */
    public function onDay(string $day): static
    {
        return $this->state(fn (array $attributes) => [
            'day_of_week' => $day,
        ]);
    }

    /**
     * Set the departure time.
     */
    public function departingAt(string $time): static
    {
        return $this->state(fn (array $attributes) => [
            'departure_time' => $time,
        ]);
    }

    /**
     * Set the driver for the schedule.
     */
    public function forDriver(User $driver): static
    {
        return $this->state(fn (array $attributes) => [
            'driver_id' => $driver->id,
        ]);
    }

    /**
     * Set the E-Jeep for the schedule.
     */
    public function withEjeep(Ejeep $ejeep): static
    {
        return $this->state(fn (array $attributes) => [
            'ejeep_id' => $ejeep->id,
        ]);
    }

    /**
     * Set the route for the schedule.
     */
    public function forRoute(Route $route): static
    {
        return $this->state(fn (array $attributes) => [
            'route_id' => $route->id,
        ]);
    }
}
