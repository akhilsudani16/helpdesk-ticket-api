<?php

use App\Models\Ticket;
use App\Models\User;
use App\Permissions\V1\Abilities;

// Priority 5: Include Parameter Tests

test('include parameter loads comments', function () {
    $customer = User::factory()->customer()->create();
    $ticket = Ticket::factory()->create(['user_id' => $customer->id]);
    $ticket->comments()->create([
        'user_id' => $customer->id,
        'body' => 'Test comment',
        'is_internal' => false,
    ]);
    
    $token = $customer->createToken('Test', Abilities::getAbilities($customer))->plainTextToken;

    $response = $this->withToken($token)
        ->getJson("/api/v1/tickets/{$ticket->id}?include=comments");

    $response->assertStatus(200);
    
    $json = $response->json();
    expect($json['status'])->toBe('success');
    expect($json['data'])->toHaveKey('comments');
    expect($json['data']['comments'])->toBeArray();
    expect($json['data']['comments'])->toHaveCount(1);
});

test('include parameter loads customer', function () {
    $customer = User::factory()->customer()->create();
    $ticket = Ticket::factory()->create(['user_id' => $customer->id]);
    
    $token = $customer->createToken('Test', Abilities::getAbilities($customer))->plainTextToken;

    $response = $this->withToken($token)
        ->getJson("/api/v1/tickets/{$ticket->id}?include=customer");

    $response->assertStatus(200);
    
    $json = $response->json();
    expect($json['data'])->toHaveKey('customer');
    expect($json['data']['customer']['id'])->toBe($customer->id);
});

test('include parameter loads assigned agent', function () {
    $agent = User::factory()->agent()->create();
    $customer = User::factory()->customer()->create();
    $ticket = Ticket::factory()->create([
        'user_id' => $customer->id,
        'assigned_to' => $agent->id,
    ]);
    
    $token = $agent->createToken('Test', Abilities::getAbilities($agent))->plainTextToken;

    $response = $this->withToken($token)
        ->getJson("/api/v1/tickets/{$ticket->id}?include=assignedAgent");

    $response->assertStatus(200);
    
    $json = $response->json();
    expect($json['data'])->toHaveKey('assigned_agent');
    expect($json['data']['assigned_agent']['id'])->toBe($agent->id);
});

test('multiple includes work together', function () {
    $agent = User::factory()->agent()->create();
    $customer = User::factory()->customer()->create();
    $ticket = Ticket::factory()->create([
        'user_id' => $customer->id,
        'assigned_to' => $agent->id,
    ]);
    $ticket->comments()->create([
        'user_id' => $customer->id,
        'body' => 'Test comment',
        'is_internal' => false,
    ]);
    
    $token = $agent->createToken('Test', Abilities::getAbilities($agent))->plainTextToken;

    $response = $this->withToken($token)
        ->getJson("/api/v1/tickets/{$ticket->id}?include=customer,assignedAgent,comments");

    $response->assertStatus(200);
    
    $json = $response->json();
    expect($json['data'])->toHaveKey('customer');
    expect($json['data'])->toHaveKey('assigned_agent');
    expect($json['data'])->toHaveKey('comments');
});

test('unsupported include returns 400', function () {
    $customer = User::factory()->customer()->create();
    $ticket = Ticket::factory()->create(['user_id' => $customer->id]);
    
    $token = $customer->createToken('Test', Abilities::getAbilities($customer))->plainTextToken;

    $response = $this->withToken($token)
        ->getJson("/api/v1/tickets/{$ticket->id}?include=invalid_relation");

    $response->assertStatus(400)
        ->assertJson([
            'status' => 'error',
            'message' => 'Unsupported include parameter.',
        ]);
});

test('unsupported include in list endpoint returns 400', function () {
    $customer = User::factory()->customer()->create();
    
    $token = $customer->createToken('Test', Abilities::getAbilities($customer))->plainTextToken;

    $response = $this->withToken($token)
        ->getJson('/api/v1/tickets?include=invalid_relation');

    $response->assertStatus(400)
        ->assertJson([
            'status' => 'error',
        ]);
});

test('include parameter works in list endpoint', function () {
    $customer = User::factory()->customer()->create();
    Ticket::factory()->count(3)->create(['user_id' => $customer->id]);
    
    $token = $customer->createToken('Test', Abilities::getAbilities($customer))->plainTextToken;

    $response = $this->withToken($token)
        ->getJson('/api/v1/tickets?include=customer');

    $response->assertStatus(200);
    
    $json = $response->json();
    foreach ($json['data'] as $ticket) {
        expect($ticket)->toHaveKey('customer');
    }
});
