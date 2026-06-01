<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Creating users...');

        // Create admin user
        User::factory()->admin()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
        ]);

        // Create agent users
        User::factory()->agent()->createMany([
            ['name' => 'Agent One', 'email' => 'agent1@example.com'],
            ['name' => 'Agent Two', 'email' => 'agent2@example.com'],
        ]);

        // Create customer users
        User::factory()->customer()->createMany([
            ['name' => 'Customer One', 'email' => 'customer1@example.com'],
            ['name' => 'Customer Two', 'email' => 'customer2@example.com'],
            ['name' => 'Customer Three', 'email' => 'customer3@example.com'],
            ['name' => 'Customer Four', 'email' => 'customer4@example.com'],
            ['name' => 'Customer Five', 'email' => 'customer5@example.com'],
        ]);

        $this->command->info('Users created successfully');
    }
}