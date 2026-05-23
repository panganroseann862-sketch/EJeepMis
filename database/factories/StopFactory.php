<?php

namespace Database\Factories;

use App\Models\Route;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Stop>
 */
class StopFactory extends Factory
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
            'stop_name' => fake()->randomElement([
                'Main Gate',
                'Library Building',
                'Engineering Complex',
                'Science Hall',
                'Administration Building',
                'Student Center',
                'Sports Complex',
                'Cafeteria',
                'Dormitory A',
                'Dormitory B',
                'Medical Center',
                'Parking Lot',
            ]) . ' Stop',
            'location_description' => fake()->optional(0.6)->sentence(),
            'latitude' => fake()->latitude(14.5, 14.7),
            'longitude' => fake()->longitude(120.9, 121.1),
            'sequence_order' => 1,
        ];
    }

    /**
     * Set the sequence order for the stop.
     */
    public function order(int $order): static
    {
        return $this->state(fn (array $attributes) => [
            'sequence_order' => $order,
        ]);
    }

    /**
     * Set the route for the stop.
     */
    public function forRoute(Route $route): static
    {
        return $this->state(fn (array $attributes) => [
            'route_id' => $route->id,
        ]);
    }
}
