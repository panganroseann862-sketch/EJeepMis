<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Ejeep>
 */
class EjeepFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'vehicle_number' => 'EJ-' . fake()->unique()->numberBetween(1000, 9999),
            'plate_number' => strtoupper(fake()->unique()->bothify('???-####')),
            'passenger_capacity' => fake()->randomElement([15, 20, 25, 30]),
            'operational_status' => 'active',
            'maintenance_notes' => null,
            'last_maintenance_date' => fake()->optional(0.3)->dateTimeBetween('-6 months', 'now'),
        ];
    }

    /**
     * Indicate that the E-Jeep is in maintenance.
     */
    public function maintenance(): static
    {
        return $this->state(fn (array $attributes) => [
            'operational_status' => 'maintenance',
            'maintenance_notes' => fake()->sentence(),
            'last_maintenance_date' => now()->subDays(fake()->numberBetween(1, 30)),
        ]);
    }

    /**
     * Indicate that the E-Jeep is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'operational_status' => 'inactive',
        ]);
    }

    /**
     * Indicate that the E-Jeep is available for assignment.
     */
    public function available(): static
    {
        return $this->state(fn (array $attributes) => [
            'operational_status' => 'active',
        ]);
    }
}
