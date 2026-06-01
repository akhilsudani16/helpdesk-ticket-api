<?php

namespace Database\Seeders;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Enums\UserRole;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Seeder;

class TicketSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Creating tickets...');

        $customers = User::where('role', UserRole::CUSTOMER)->get();
        $agents = User::where('role', UserRole::AGENT)->get();

        $ticketData = $this->generateTicketDistribution();

        foreach ($ticketData as $data) {
            $customer = $customers->random();
            $assignedAgent = $data['assigned'] ? $agents->random() : null;

            Ticket::factory()
                ->for($customer, 'customer')
                ->state([
                    'status' => $data['status'],
                    'priority' => $data['priority'],
                    'assigned_to' => $assignedAgent?->id,
                ])
                ->create();
        }

        $this->command->info('Tickets created successfully');
    }

    private function generateTicketDistribution(): array
    {
        $tickets = [];

       $statusDistribution = [
            TicketStatus::OPEN->value => 12,
            TicketStatus::IN_PROGRESS->value => 9,
            TicketStatus::RESOLVED->value => 6,
            TicketStatus::CLOSED->value => 3,
        ];

      $priorityDistribution = [
            TicketPriority::URGENT->value => 3,
            TicketPriority::HIGH->value => 6,
            TicketPriority::MEDIUM->value => 15,
            TicketPriority::LOW->value => 6,
        ];

        foreach ($statusDistribution as $status => $count) {
            for ($i = 0; $i < $count; $i++) {
                $priority = $this->getRandomPriorityByWeight($priorityDistribution);
                
                $tickets[] = [
                    'status' => $status,
                    'priority' => $priority,
                    'assigned' => $status !== TicketStatus::OPEN ? fake()->boolean(80) : fake()->boolean(30),
                ];
            }
        }

        return $tickets;
    }

    private function getRandomPriorityByWeight(array $distribution): TicketPriority
    {
        $priorities = [];
        foreach ($distribution as $priority => $weight) {
            $priorities = array_merge($priorities, array_fill(0, $weight, $priority));
        }

        return TicketPriority::from(fake()->randomElement($priorities));
    }
}