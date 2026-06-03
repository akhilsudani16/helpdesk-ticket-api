<?php

use App\Models\Ticket;
use App\Models\User;
use App\Permissions\V1\Abilities;

test('ticket resource does not expose sensitive user data', function () {
    $customer = User::factory()->customer()->create();
    $ticket = Ticket::factory()->create(['user_id' => $customer->id]);
    
    $token = $customer->createToken('Test', [Abilities::ViewTickets])->plainTextToken;

    $response = $this->withToken($token)
        ->getJson("/api/v1/tickets/{$ticket->id}");

    $response->assertStatus(200);
    
    $json = $response->json('data');
    
    // Should have customer data
    expect($json)->toHaveKey('customer');
    expect($json['customer'])->toHaveKey('id');
    expect($json['customer'])->toHaveKey('name');
    expect($json['customer'])->toHaveKey('email');
    
    // Should NOT have sensitive fields
    expect($json['customer'])->not->toHaveKey('password');
    expect($json['customer'])->not->toHaveKey('remember_token');
});

test('ticket resource only includes relationships when loaded', function () {
    $customer = User::factory()->customer()->create();
    $ticket = Ticket::factory()->create(['user_id' => $customer->id]);
    $ticket->comments()->create([
        'user_id' => $customer->id,
        'body' => 'Test comment',
        'is_internal' => false,
    ]);
    
    $token = $customer->createToken('Test', [Abilities::ViewTickets])->plainTextToken;

    // Without include parameter
    $response = $this->withToken($token)
        ->getJson("/api/v1/tickets/{$ticket->id}");

    $response->assertStatus(200);
    $json = $response->json('data');
    
    // Should have customer (always loaded)
    expect($json)->toHaveKey('customer');
    
    // Should NOT have comments (not requested)
    expect($json)->not->toHaveKey('comments');
});

test('ticket resource includes comments when requested', function () {
    $customer = User::factory()->customer()->create();
    $ticket = Ticket::factory()->create(['user_id' => $customer->id]);
    $ticket->comments()->create([
        'user_id' => $customer->id,
        'body' => 'Test comment',
        'is_internal' => false,
    ]);
    
    $token = $customer->createToken('Test', [Abilities::ViewTickets])->plainTextToken;

    // With include parameter
    $response = $this->withToken($token)
        ->getJson("/api/v1/tickets/{$ticket->id}?include=comments");

    $response->assertStatus(200);
    $json = $response->json('data');
    
    // Should have comments
    expect($json)->toHaveKey('comments');
    expect($json['comments'])->toBeArray();
    expect(count($json['comments']))->toBeGreaterThan(0);
});

test('comment resource hides is_internal from customers', function () {
    $customer = User::factory()->customer()->create();
    $ticket = Ticket::factory()->create(['user_id' => $customer->id]);
    $ticket->comments()->create([
        'user_id' => $customer->id,
        'body' => 'Public comment',
        'is_internal' => false,
    ]);
    
    $token = $customer->createToken('Test', [Abilities::ViewTickets, Abilities::ViewComments])->plainTextToken;

    $response = $this->withToken($token)
        ->getJson("/api/v1/tickets/{$ticket->id}/comments");

    $response->assertStatus(200);
    $json = $response->json('data');
    
    // Should not have is_internal field for customers
    expect($json[0])->not->toHaveKey('is_internal');
});

test('comment resource shows is_internal to agents', function () {
    $agent = User::factory()->agent()->create();
    $customer = User::factory()->customer()->create();
    $ticket = Ticket::factory()->create(['user_id' => $customer->id, 'assigned_to' => $agent->id]);
    $ticket->comments()->create([
        'user_id' => $agent->id,
        'body' => 'Internal note',
        'is_internal' => true,
    ]);
    
    $token = $agent->createToken('Test', [Abilities::ViewTickets, Abilities::ViewComments])->plainTextToken;

    $response = $this->withToken($token)
        ->getJson("/api/v1/tickets/{$ticket->id}/comments");

    $response->assertStatus(200);
    $json = $response->json('data');
    
    // Should have is_internal field for agents
    expect($json[0])->toHaveKey('is_internal');
    expect($json[0]['is_internal'])->toBe(true);
});

test('user resource never exposes password', function () {
    $admin = User::factory()->admin()->create();
    
    $token = $admin->createToken('Test', [Abilities::ViewUsers])->plainTextToken;

    $response = $this->withToken($token)
        ->getJson('/api/v1/users');

    $response->assertStatus(200);
    $json = $response->json('data');
    
    foreach ($json as $user) {
        expect($user)->not->toHaveKey('password');
        expect($user)->not->toHaveKey('remember_token');
    }
});

test('ticket collection includes pagination metadata', function () {
    $customer = User::factory()->customer()->create();
    Ticket::factory()->count(25)->create(['user_id' => $customer->id]);
    
    $token = $customer->createToken('Test', [Abilities::ViewTickets])->plainTextToken;

    $response = $this->withToken($token)
        ->getJson('/api/v1/tickets?per_page=10');

    $response->assertStatus(200);
    
    // Should have pagination structure
    expect($response->json())->toHaveKey('data');
    expect($response->json())->toHaveKey('meta');
    expect($response->json())->toHaveKey('links');
    
    // Meta should have pagination info
    $meta = $response->json('meta');
    expect($meta)->toHaveKey('current_page');
    expect($meta)->toHaveKey('per_page');
    expect($meta)->toHaveKey('total');
    expect($meta['total'])->toBeGreaterThanOrEqual(25);
});
