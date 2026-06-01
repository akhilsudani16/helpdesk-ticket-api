<?php

use App\Models\User;
use App\Permissions\V1\Abilities;

// Priority 5: Ticket Creation Tests

test('customer can create own ticket', function () {
    $customer = User::factory()->customer()->create();
    $token = $customer->createToken('Test', Abilities::getAbilities($customer))->plainTextToken;

    $response = $this->withToken($token)
        ->postJson('/api/v1/tickets', [
            'title' => 'Test Ticket Title',
            'description' => 'This is a test ticket description that is long enough to pass validation.',
            'priority' => 'high',
        ]);

    $response->assertStatus(201)
        ->assertJson([
            'status' => 'success',
            'message' => 'Ticket created successfully.',
        ]);

    $this->assertDatabaseHas('tickets', [
        'title' => 'Test Ticket Title',
        'user_id' => $customer->id,
        'status' => 'open', // Default status
    ]);
});

test('admin can create ticket for another user', function () {
    $admin = User::factory()->admin()->create();
    $customer = User::factory()->customer()->create();
    
    $token = $admin->createToken('Test', Abilities::getAbilities($admin))->plainTextToken;

    $response = $this->withToken($token)
        ->postJson('/api/v1/tickets', [
            'title' => 'Admin Created Ticket',
            'description' => 'This ticket was created by an admin for a customer.',
            'priority' => 'medium',
            'user_id' => $customer->id,
        ]);

    $response->assertStatus(201);

    $this->assertDatabaseHas('tickets', [
        'title' => 'Admin Created Ticket',
        'user_id' => $customer->id,
    ]);
});

test('customer cannot create ticket for another user', function () {
    $customer1 = User::factory()->customer()->create();
    $customer2 = User::factory()->customer()->create();
    
    $token = $customer1->createToken('Test', Abilities::getAbilities($customer1))->plainTextToken;

    $response = $this->withToken($token)
        ->postJson('/api/v1/tickets', [
            'title' => 'Unauthorized Ticket',
            'description' => 'Trying to create a ticket for another user.',
            'priority' => 'low',
            'user_id' => $customer2->id,
        ]);

    // Should return 422 because user_id is prohibited for customers
    $response->assertStatus(422)
        ->assertJsonValidationErrors(['user_id']);
});

test('ticket creation requires valid priority', function () {
    $customer = User::factory()->customer()->create();
    $token = $customer->createToken('Test', Abilities::getAbilities($customer))->plainTextToken;

    $response = $this->withToken($token)
        ->postJson('/api/v1/tickets', [
            'title' => 'Test Ticket',
            'description' => 'This is a test ticket description.',
            'priority' => 'invalid_priority',
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['priority']);
});
