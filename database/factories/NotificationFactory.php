<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Notification>
 */
class NotificationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $types = ['route_update', 'schedule_change', 'capacity_warning'];
        $type = fake()->randomElement($types);
        
        return [
            'user_id' => User::factory()->driver(),
            'type' => $type,
            'title' => $this->getTitleForType($type),
            'message' => $this->getMessageForType($type),
            'data' => $this->getDataForType($type),
            'is_read' => false,
            'read_at' => null,
        ];
    }

    /**
     * Indicate that the notification is a route update.
     */
    public function routeUpdate(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'route_update',
            'title' => 'Route Updated',
            'message' => 'Your assigned route has been updated. Please review the changes.',
            'data' => [
                'route_id' => fake()->numberBetween(1, 10),
                'route_name' => 'Route ' . fake()->numberBetween(1, 10),
            ],
        ]);
    }

    /**
     * Indicate that the notification is a schedule change.
     */
    public function scheduleChange(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'schedule_change',
            'title' => 'Schedule Changed',
            'message' => 'Your schedule has been modified. Check your updated assignments.',
            'data' => [
                'schedule_id' => fake()->numberBetween(1, 20),
                'departure_time' => fake()->time('H:i'),
            ],
        ]);
    }

    /**
     * Indicate that the notification is a capacity warning.
     */
    public function capacityWarning(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'capacity_warning',
            'title' => 'Capacity Warning',
            'message' => 'Your E-Jeep is at or over capacity. Please monitor passenger load.',
            'data' => [
                'trip_id' => fake()->numberBetween(1, 50),
                'current_count' => fake()->numberBetween(20, 30),
                'capacity' => 20,
            ],
        ]);
    }

    /**
     * Indicate that the notification has been read.
     */
    public function read(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_read' => true,
            'read_at' => fake()->dateTimeBetween('-1 week', 'now'),
        ]);
    }

    /**
     * Indicate that the notification is unread.
     */
    public function unread(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_read' => false,
            'read_at' => null,
        ]);
    }

    /**
     * Set the notification for a specific user.
     */
    public function forUser(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user->id,
        ]);
    }

    /**
     * Get title for notification type.
     */
    private function getTitleForType(string $type): string
    {
        return match ($type) {
            'route_update' => 'Route Updated',
            'schedule_change' => 'Schedule Changed',
            'capacity_warning' => 'Capacity Warning',
            default => 'Notification',
        };
    }

    /**
     * Get message for notification type.
     */
    private function getMessageForType(string $type): string
    {
        return match ($type) {
            'route_update' => 'Your assigned route has been updated. Please review the changes.',
            'schedule_change' => 'Your schedule has been modified. Check your updated assignments.',
            'capacity_warning' => 'Your E-Jeep is at or over capacity. Please monitor passenger load.',
            default => fake()->sentence(),
        };
    }

    /**
     * Get data for notification type.
     */
    private function getDataForType(string $type): array
    {
        return match ($type) {
            'route_update' => [
                'route_id' => fake()->numberBetween(1, 10),
                'route_name' => 'Route ' . fake()->numberBetween(1, 10),
            ],
            'schedule_change' => [
                'schedule_id' => fake()->numberBetween(1, 20),
                'departure_time' => fake()->time('H:i'),
            ],
            'capacity_warning' => [
                'trip_id' => fake()->numberBetween(1, 50),
                'current_count' => fake()->numberBetween(20, 30),
                'capacity' => 20,
            ],
            default => [],
        };
    }
}
