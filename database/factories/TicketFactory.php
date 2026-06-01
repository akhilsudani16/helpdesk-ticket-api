<?php

namespace Database\Factories;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Ticket>
 */
class TicketFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(5),
            'description' => fake()->paragraph(3),
            'status' => fake()->randomElement(TicketStatus::values()),
            'priority' => fake()->randomElement(TicketPriority::values()),
            'user_id' => User::factory(),
            'assigned_to' => null,
        ];
    }

    /**
     * Indicate that the ticket is assigned to a user.
     */
    public function assignedTo(?User $user = null): static
    {
        return $this->state(fn (array $attributes) => [
            'assigned_to' => $user?->id ?? User::factory()->agent(),
        ]);
    }

    /**
     * Indicate that the ticket is open.
     */
    public function open(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => TicketStatus::OPEN,
        ]);
    }

    /**
     * Indicate that the ticket is in progress.
     */
    public function inProgress(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => TicketStatus::IN_PROGRESS,
        ]);
    }

    /**
     * Indicate that the ticket is resolved.
     */
    public function resolved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => TicketStatus::RESOLVED,
        ]);
    }

    /**
     * Indicate that the ticket is closed.
     */
    public function closed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => TicketStatus::CLOSED,
        ]);
    }

    /**
     * Indicate that the ticket has low priority.
     */
    public function lowPriority(): static
    {
        return $this->state(fn (array $attributes) => [
            'priority' => TicketPriority::LOW,
        ]);
    }

    /**
     * Indicate that the ticket has medium priority.
     */
    public function mediumPriority(): static
    {
        return $this->state(fn (array $attributes) => [
            'priority' => TicketPriority::MEDIUM,
        ]);
    }

    /**
     * Indicate that the ticket has high priority.
     */
    public function highPriority(): static
    {
        return $this->state(fn (array $attributes) => [
            'priority' => TicketPriority::HIGH,
        ]);
    }

    /**
     * Indicate that the ticket has urgent priority.
     */
    public function urgentPriority(): static
    {
        return $this->state(fn (array $attributes) => [
            'priority' => TicketPriority::URGENT,
        ]);
    }
}
