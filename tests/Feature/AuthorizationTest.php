<?php

use App\Models\Ticket;
use App\Models\User;
use App\Permissions\V1\Abilities;

// Priority 5: Authorization Tests

test('unauthenticated user cannot list tickets', function () {
    $response = $this->getJson('/api/v1/tickets');

    $response->assertStatus(401)
        ->assertJson([
            'status' => 'error',
            'message' => 'Unauthenticated.',
        ]);
});

test('customer cannot view other customer ticket', function () {
    $customer1 = User::factory()->customer()->create();
    $customer2 = User::factory()->customer()->create();
    
    $ticket = Ticket::factory()->create(['user_id' => $customer1->id]);
    
    $token = $customer2->createToken('Test', Abilities::getAbilities($customer2))->plainTextToken;

    $response = $this->withToken($token)
        ->getJson("/api/v1/tickets/{$ticket->id}");

    $response->assertStatus(403)
        ->assertJson([
            'status' => 'error',
        ]);
});

test('admin can view all tickets', function () {
    $admin = User::factory()->admin()->create();
    $customer = User::factory()->customer()->create();
    
    $ticket = Ticket::factory()->create(['user_id' => $customer->id]);
    
    $token = $admin->createToken('Test', Abilities::getAbilities($admin))->plainTextToken;

    $response = $this->withToken($token)
        ->getJson("/api/v1/tickets/{$ticket->id}");

    $response->assertStatus(200)
        ->assertJson([
            'status' => 'success',
        ]);
});

test('agent can view assigned ticket', function () {
    $agent = User::factory()->agent()->create();
    $customer = User::factory()->customer()->create();
    
    $ticket = Ticket::factory()->create([
        'user_id' => $customer->id,
        'assigned_to' => $agent->id,
    ]);
    
    $token = $agent->createToken('Test', Abilities::getAbilities($agent))->plainTextToken;

    $response = $this->withToken($token)
        ->getJson("/api/v1/tickets/{$ticket->id}");

    $response->assertStatus(200);
});

test('agent cannot view unassigned ticket', function () {
    $agent = User::factory()->agent()->create();
    $customer = User::factory()->customer()->create();
    
    $ticket = Ticket::factory()->create([
        'user_id' => $customer->id,
        'assigned_to' => null,
    ]);
    
    $token = $agent->createToken('Test', Abilities::getAbilities($agent))->plainTextToken;

    $response = $this->withToken($token)
        ->getJson("/api/v1/tickets/{$ticket->id}");

    $response->assertStatus(403);
});

test('customer can view own ticket', function () {
    $customer = User::factory()->customer()->create();
    $ticket = Ticket::factory()->create(['user_id' => $customer->id]);
    
    $token = $customer->createToken('Test', Abilities::getAbilities($customer))->plainTextToken;

    $response = $this->withToken($token)
        ->getJson("/api/v1/tickets/{$ticket->id}");

    $response->assertStatus(200);
});
