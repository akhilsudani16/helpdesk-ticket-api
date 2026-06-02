<?php

namespace Database\Seeders;

use App\Enums\TicketStatus;
use App\Enums\UserRole;
use App\Models\Ticket;
use App\Models\TicketComment;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Starting database seeding...');

        // Disable foreign key checks for truncation
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        
        // Truncate tables for clean seeding
        $this->command->info('Cleaning existing data...');
        
        DB::table('ticket_comments')->truncate();
        DB::table('tickets')->truncate();
        DB::table('users')->truncate();
        
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
        $this->command->info('');
        $this->command->info('Seeding Summary:');
        $this->command->info('   Users: ' . User::count());
        $this->command->info('   Tickets: ' . Ticket::count());
        $this->command->info('   Comments: ' . TicketComment::count());
        $this->command->info('');

        $this->command->info('User Roles:');
        $this->command->info('   Admins: ' . User::where('role', UserRole::ADMIN)->count());
        $this->command->info('   Agents: ' . User::where('role', UserRole::AGENT)->count());
        $this->command->info('   Customers: ' . User::where('role', UserRole::CUSTOMER)->count());
        $this->command->info('');

        $this->command->info('Ticket Status:');
        foreach (TicketStatus::cases() as $status) {
            $count = Ticket::where('status', $status)->count();
            $this->command->info("   {$status->label()}: {$count}");
        }
    }
}