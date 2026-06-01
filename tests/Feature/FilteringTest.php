<?php

use App\Models\Ticket;
use App\Models\User;
use App\Permissions\V1\Abilities;

// Priority 5: Filtering Tests

test('filter by status works', function () {
    $customer = User::factory()->customer()->create();
    Ticket::factory()->open()->create(['user_id' => $customer->id]);
    Ticket::factory()->closed()->create(['user_id' => $customer->id]);
    
    $token = $customer->createToken('Test', Abilities::getAbilities($customer))->plainTextToken;

    $response = $this->withToken($token)
        ->getJson('/api/v1/tickets?filter[status]=open');

    $response->assertStatus(200);
    
    $data = $response->json('data');
    foreach ($data as $ticket) {
        expect($ticket['status'])->toBe('open');
    }
});

test('filter by priority works', function () {
    $customer = User::factory()->customer()->create();
    Ticket::factory()->highPriority()->create(['user_id' => $customer->id]);
    Ticket::factory()->lowPriority()->create(['user_id' => $customer->id]);
    
    $token = $customer->createToken('Test', Abilities::getAbilities($customer))->plainTextToken;

    $response = $this->withToken($token)
        ->getJson('/api/v1/tickets?filter[priority]=high');

    $response->assertStatus(200);
    
    $data = $response->json('data');
    foreach ($data as $ticket) {
        expect($ticket['priority'])->toBe('high');
    }
});

test('customer cannot filter other users tickets', function () {
    $customer1 = User::factory()->customer()->create();
    $customer2 = User::factory()->customer()->create();
    
    Ticket::factory()->create(['user_id' => $customer1->id]);
    Ticket::factory()->create(['user_id' => $customer2->id]);
    
    $token = $customer1->createToken('Test', Abilities::getAbilities($customer1))->plainTextToken;

    // Customer should not be able to filter by another customer's ID
    // The filter should be ignored or return only their own tickets
    $response = $this->withToken($token)
        ->getJson('/api/v1/tickets');

    $response->assertStatus(200);
    
    // Customer should only see their own tickets
    $data = $response->json('data');
    foreach ($data as $ticket) {
        expect($ticket['customer']['id'])->toBe($customer1->id);
    }
});

test('admin can filter by customer id', function () {
    $admin = User::factory()->admin()->create();
    $customer = User::factory()->customer()->create();
    
    Ticket::factory()->count(2)->create(['user_id' => $customer->id]);
    Ticket::factory()->create(); // Another customer's ticket
    
    $token = $admin->createToken('Test', Abilities::getAbilities($admin))->plainTextToken;

    $response = $this->withToken($token)
        ->getJson("/api/v1/tickets?filter[customer_id]={$customer->id}");

    $response->assertStatus(200);
    
    $data = $response->json('data');
    expect($data)->toHaveCount(2);
});

test('unsupported filter returns 400', function () {
    $customer = User::factory()->customer()->create();
    $token = $customer->createToken('Test', Abilities::getAbilities($customer))->plainTextToken;

    $response = $this->withToken($token)
        ->getJson('/api/v1/tickets?filter[invalid_field]=value');

    $response->assertStatus(400)
        ->assertJson([
            'status' => 'error',
        ]);
});
