<?php

namespace Database\Seeders;

use App\Models\Ticket;
use App\Models\TicketComment;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create admin user
        $admin = User::factory()->admin()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
        ]);

        // Create agent users
        $agent1 = User::factory()->agent()->create([
            'name' => 'Agent One',
            'email' => 'agent1@example.com',
        ]);

        $agent2 = User::factory()->agent()->create([
            'name' => 'Agent Two',
            'email' => 'agent2@example.com',
        ]);

        // Create customer users
        $customer1 = User::factory()->customer()->create([
            'name' => 'Customer One',
            'email' => 'customer1@example.com',
        ]);

        $customer2 = User::factory()->customer()->create([
            'name' => 'Customer Two',
            'email' => 'customer2@example.com',
        ]);

        $customer3 = User::factory()->customer()->create([
            'name' => 'Customer Three',
            'email' => 'customer3@example.com',
        ]);

        $customer4 = User::factory()->customer()->create([
            'name' => 'Customer Four',
            'email' => 'customer4@example.com',
        ]);

        $customer5 = User::factory()->customer()->create([
            'name' => 'Customer Five',
            'email' => 'customer5@example.com',
        ]);

        $customers = collect([$customer1, $customer2, $customer3, $customer4, $customer5]);

        // Get all agents and customers
        $agents = User::where('role', 'agent')->get();

        // Create tickets
        for ($i = 0; $i < 30; $i++) {
            $customer = $customers->random();
            $assignedAgent = fake()->boolean(70) ? $agents->random() : null;

            $ticket = Ticket::factory()
                ->for($customer, 'customer')
                ->when($assignedAgent, fn ($query) => $query->state(['assigned_to' => $assignedAgent->id]))
                ->create();

            // Create comments for each ticket (at least 2)
            $commentCount = fake()->numberBetween(2, 5);
            for ($j = 0; $j < $commentCount; $j++) {
                // 70% chance the comment is from customer, 30% from agents
                $commentAuthor = fake()->boolean(70) ? $customer : $agents->random();

                // Customers can only create public comments
                $isInternal = ($commentAuthor->isAgent() || $commentAuthor->isAdmin()) && fake()->boolean(50);

                TicketComment::factory()
                    ->for($ticket)
                    ->for($commentAuthor, 'user')
                    ->state(['is_internal' => $isInternal])
                    ->create();
            }
        }
    }
}

