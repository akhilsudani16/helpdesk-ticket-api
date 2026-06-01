<?php

namespace Database\Seeders;

use App\Enums\TicketStatus;
use App\Enums\UserRole;
use App\Models\Ticket;
use App\Models\TicketComment;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->command->info('Starting database seeding...');

        // Disable foreign key checks for better performance
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // Truncate tables for clean seeding
        $this->command->info('Cleaning existing data...');
        TicketComment::truncate();
        Ticket::truncate();
        User::truncate();

        // Re-enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // Call individual seeders
        $this->call([
            UserSeeder::class,
            TicketSeeder::class,
            TicketCommentSeeder::class,
        ]);

        $this->command->info('Database seeding completed successfully!');
        $this->printSeedingSummary();
    }

    private function printSeedingSummary(): void
    {
        $userCount = User::count();
        $ticketCount = Ticket::count();
        $commentCount = TicketComment::count();

        $this->command->info('');
        $this->command->info('Seeding Summary:');
        $this->command->info("   Users: {$userCount}");
        $this->command->info("   Tickets: {$ticketCount}");
        $this->command->info("   Comments: {$commentCount}");
        $this->command->info('');

        // Role breakdown
        $adminCount = User::where('role', UserRole::ADMIN)->count();
        $agentCount = User::where('role', UserRole::AGENT)->count();
        $customerCount = User::where('role', UserRole::CUSTOMER)->count();

        $this->command->info('User Roles:');
        $this->command->info("   Admins: {$adminCount}");
        $this->command->info("   Agents: {$agentCount}");
        $this->command->info("   Customers: {$customerCount}");
        $this->command->info('');

        // Status breakdown
        foreach (TicketStatus::cases() as $status) {
            $count = Ticket::where('status', $status)->count();
            $this->command->info("   {$status->label()}: {$count}");
        }
    }
}