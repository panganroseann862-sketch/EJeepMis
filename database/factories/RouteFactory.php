<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Route>
 */
class RouteFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $routeNumber = fake()->unique()->numberBetween(1, 99);
        
        return [
            'route_name' => fake()->randomElement([
                'Main Campus Loop',
                'North Campus Express',
                'South Campus Route',
                'East Gate Circuit',
                'West Wing Shuttle',
                'Central Hub Line',
            ]) . ' ' . $routeNumber,
            'route_code' => 'R' . str_pad($routeNumber, 3, '0', STR_PAD_LEFT),
            'description' => fake()->optional(0.7)->sentence(),
            'status' => 'active',
        ];
    }

    /**
     * Indicate that the route is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'inactive',
        ]);
    }
}
