<?php

use App\Models\Ticket;
use App\Models\User;

// Test 1: Unsupported include validation
test('unsupported include parameter is silently ignored', function () {
    $customer = User::factory()->customer()->create();
    $ticket = Ticket::factory()->create(['user_id' => $customer->id]);

    $token = $customer->createToken('Test', ['tickets:view'])->plainTextToken;

    $response = $this->withToken($token)
        ->getJson("/api/v1/tickets/{$ticket->id}?include=invalidField,hackerField");


    $response->assertStatus(200);

    $json = $response->json('data');
    expect($json)->not->toHaveKey('invalidField');
    expect($json)->not->toHaveKey('hackerField');
});

// Test 2: PUT requires all fields
test('PUT request requires all fields', function () {
    $admin = User::factory()->admin()->create();
    $customer = User::factory()->customer()->create();
    $ticket = Ticket::factory()->create(['user_id' => $customer->id]);

    $token = $admin->createToken('Test', [
        'tickets:view',
        'tickets:update',
        'tickets:update-any'
    ])->plainTextToken;


    $response = $this->withToken($token)
        ->putJson("/api/v1/tickets/{$ticket->id}", [
            'title' => 'Updated Title Here',
            'status' => 'in_progress',
            'priority' => 'high',
            'assigned_to' => null,
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['description']);
});

// Test 3: PUT with all fields succeeds
test('PUT request with all fields succeeds', function () {
    $admin = User::factory()->admin()->create();
    $customer = User::factory()->customer()->create();
    $ticket = Ticket::factory()->create(['user_id' => $customer->id]);

    $token = $admin->createToken('Test', [
        'tickets:view',
        'tickets:update',
        'tickets:update-any'
    ])->plainTextToken;

    $response = $this->withToken($token)
        ->putJson("/api/v1/tickets/{$ticket->id}", [
            'title' => 'Complete Replacement Title',
            'description' => 'This is a complete replacement description that is long enough.',
            'status' => 'in_progress',
            'priority' => 'urgent',
            'assigned_to' => $admin->id,
        ]);

    $response->assertStatus(200);

    $ticket->refresh();
    expect($ticket->title)->toBe('Complete Replacement Title');
    expect($ticket->status)->toBe('in_progress');
    expect($ticket->priority)->toBe('urgent');
});

// Test 4: Customer cannot use PUT
test('customer cannot use PUT to update ticket', function () {
    $customer = User::factory()->customer()->create();
    $ticket = Ticket::factory()->create(['user_id' => $customer->id]);

    $token = $customer->createToken('Test', ['tickets:view', 'tickets:update'])->plainTextToken;

    $response = $this->withToken($token)
        ->putJson("/api/v1/tickets/{$ticket->id}", [
            'title' => 'Updated Title Here',
            'description' => 'This is a complete description that is long enough for validation.',
            'status' => 'open',
            'priority' => 'high',
            'assigned_to' => null,
        ]);

    $response->assertStatus(403)
        ->assertJson([
            'status' => 'error',
            'message' => 'Customers must use PATCH for partial updates.',
        ]);
});

// Test 5: Token ability restriction - customer without tickets:create cannot create
test('customer without tickets:create ability cannot create ticket', function () {
    $customer = User::factory()->customer()->create();
    $token = $customer->createToken('Test', ['tickets:view'])->plainTextToken; // No tickets:create

    $response = $this->withToken($token)
        ->postJson('/api/v1/tickets', [
            'title' => 'Test Ticket Title',
            'description' => 'This is a test ticket description that is long enough.',
            'priority' => 'high',
        ]);

    $response->assertStatus(403);
});

// Test 6: Token ability restriction - agent without comments:create-internal cannot create internal comment
test('agent without create-internal ability cannot create internal comment', function () {
    $agent = User::factory()->agent()->create();
    $customer = User::factory()->customer()->create();
    $ticket = Ticket::factory()->create(['user_id' => $customer->id, 'assigned_to' => $agent->id]);

    $token = $agent->createToken('Test', [
        'tickets:view',
        'comments:view',
        'comments:create'
    ])->plainTextToken;

    $response = $this->withToken($token)
        ->postJson("/api/v1/tickets/{$ticket->id}/comments", [
            'body' => 'Attempting internal comment.',
            'is_internal' => true,
        ]);

    $response->assertStatus(403)
        ->assertJson([
            'status' => 'error',
            'message' => 'You cannot create internal comments.',
        ]);
});

// Test 7: Privilege escalation - customer cannot escalate via assigned_to
test('customer cannot escalate privileges via assigned_to field', function () {
    $customer = User::factory()->customer()->create();
    $agent = User::factory()->agent()->create();
    $ticket = Ticket::factory()->create(['user_id' => $customer->id, 'assigned_to' => null]);

    $token = $customer->createToken('Test', ['tickets:view', 'tickets:update'])->plainTextToken;

    $response = $this->withToken($token)
        ->patchJson("/api/v1/tickets/{$ticket->id}", [
            'title' => 'Updated title for testing',
            'assigned_to' => $agent->id, // Trying to assign to agent
        ]);

    $response->assertStatus(200);

    $ticket->refresh();
    expect($ticket->assigned_to)->toBeNull();
});

// Test 8: Privilege escalation - customer cannot change status to closed
test('customer cannot change ticket status to closed', function () {
    $customer = User::factory()->customer()->create();
    $ticket = Ticket::factory()->create(['user_id' => $customer->id, 'status' => 'open']);

    $token = $customer->createToken('Test', ['tickets:view', 'tickets:update'])->plainTextToken;

    $response = $this->withToken($token)
        ->patchJson("/api/v1/tickets/{$ticket->id}", [
            'status' => 'closed',
        ]);

    $response->assertStatus(200);

    $ticket->refresh();
    expect($ticket->status)->toBe('open');
});

// Test 9: Multi-role authorization - admin can update any ticket
test('admin can update any ticket regardless of ownership', function () {
    $admin = User::factory()->admin()->create();
    $customer = User::factory()->customer()->create();
    $ticket = Ticket::factory()->create(['user_id' => $customer->id]);

    $token = $admin->createToken('Test', [
        'tickets:view',
        'tickets:update',
        'tickets:update-any'
    ])->plainTextToken;

    $response = $this->withToken($token)
        ->patchJson("/api/v1/tickets/{$ticket->id}", [
            'status' => 'resolved',
            'priority' => 'low',
        ]);

    $response->assertStatus(200);

    $ticket->refresh();
    expect($ticket->status)->toBe('resolved');
    expect($ticket->priority)->toBe('low');
});

// Test 10: Multi-role authorization - agent cannot update a ticket not assigned to them
test('agent without update-any cannot update unassigned ticket', function () {
    $agent = User::factory()->agent()->create();
    $customer = User::factory()->customer()->create();
    $ticket = Ticket::factory()->create([
        'user_id' => $customer->id,
        'assigned_to' => null,
    ]);

    $token = $agent->createToken('Test', ['tickets:view', 'tickets:update'])->plainTextToken;

    $response = $this->withToken($token)
        ->patchJson("/api/v1/tickets/{$ticket->id}", [
            'status' => 'in_progress',
        ]);

    $response->assertStatus(403);
});

// Test 11: Consistent JSON error for 404
test('accessing non-existent ticket returns consistent JSON 404', function () {
    $customer = User::factory()->customer()->create();
    $token = $customer->createToken('Test', ['tickets:view'])->plainTextToken;

    $response = $this->withToken($token)
        ->getJson('/api/v1/tickets/99999');

    $response->assertStatus(404)
        ->assertJson([
            'status' => 'error',
        ]);
});

// Test 12: Consistent JSON error for validation (422)
test('validation errors return consistent JSON format', function () {
    $customer = User::factory()->customer()->create();
    $token = $customer->createToken('Test', ['tickets:view', 'tickets:create'])->plainTextToken;

    $response = $this->withToken($token)
        ->postJson('/api/v1/tickets', [
            'title' => 'Too',
            'description' => 'Short',
            'priority' => 'invalid',
        ]);

    $response->assertStatus(422)
        ->assertJsonStructure([
            'message',
            'errors',
        ])
        ->assertJsonValidationErrors(['title', 'description', 'priority']);
});

// Test 13: Soft delete - admin can restore deleted ticket
test('admin can restore soft deleted ticket', function () {
    $admin = User::factory()->admin()->create();
    $customer = User::factory()->customer()->create();
    $ticket = Ticket::factory()->create(['user_id' => $customer->id]);

    $ticket->delete();

    $token = $admin->createToken('Test', ['tickets:view', 'tickets:delete-any'])->plainTextToken;

    $ticket->restore();

    expect($ticket->trashed())->toBeFalse();
    $this->assertDatabaseHas('tickets', [
        'id' => $ticket->id,
        'deleted_at' => null,
    ]);
});

// Test 14: Soft delete - customer cannot access deleted ticket
test('customer cannot access soft deleted ticket', function () {
    $customer = User::factory()->customer()->create();
    $ticket = Ticket::factory()->create(['user_id' => $customer->id]);

    // Soft delete the ticket
    $ticket->delete();

    $token = $customer->createToken('Test', ['tickets:view'])->plainTextToken;

    $response = $this->withToken($token)
        ->getJson("/api/v1/tickets/{$ticket->id}");

    $response->assertStatus(404);
});

// Test 15: Multiple sort fields work correctly
test('multiple sort fields work correctly', function () {
    $customer = User::factory()->customer()->create();

    $ticket1 = Ticket::factory()->create([
        'user_id' => $customer->id,
        'priority' => 'high',
        'created_at' => now()->subDays(2),
    ]);
    $ticket2 = Ticket::factory()->create([
        'user_id' => $customer->id,
        'priority' => 'high',
        'created_at' => now()->subDay(),
    ]);
    $ticket3 = Ticket::factory()->create([
        'user_id' => $customer->id,
        'priority' => 'low',
        'created_at' => now(),
    ]);

    $token = $customer->createToken('Test', ['tickets:view'])->plainTextToken;

    $response = $this->withToken($token)
        ->getJson('/api/v1/tickets?sort=priority,-created_at');

    $response->assertStatus(200);

    $data = $response->json('data');
    expect($data[0]['priority'])->toBe('high');
});

// Test 16: Revoked token cannot access API
test('revoked token cannot access protected endpoints', function () {
    $customer = User::factory()->customer()->create();
    $tokenResult = $customer->createToken('Test', ['tickets:view']);
    $token = $tokenResult->plainTextToken;
    $tokenId = $tokenResult->accessToken->id;

    $customer->tokens()->where('id', $tokenId)->delete();

    $response = $this->withToken($token)
        ->getJson('/api/v1/tickets');

    $response->assertStatus(401);
});

// Test 17: Filter by multiple criteria works
test('filtering by multiple criteria works correctly', function () {
    $customer = User::factory()->customer()->create();

    Ticket::factory()->create([
        'user_id' => $customer->id,
        'status' => 'open',
        'priority' => 'high',
    ]);
    Ticket::factory()->create([
        'user_id' => $customer->id,
        'status' => 'open',
        'priority' => 'low',
    ]);
    Ticket::factory()->create([
        'user_id' => $customer->id,
        'status' => 'closed',
        'priority' => 'high',
    ]);

    $token = $customer->createToken('Test', ['tickets:view'])->plainTextToken;

    $response = $this->withToken($token)
        ->getJson('/api/v1/tickets?filter[status]=open&filter[priority]=high');

    $response->assertStatus(200);

    $data = $response->json('data');
    expect(count($data))->toBe(1);
    expect($data[0]['status'])->toBe('open');
    expect($data[0]['priority'])->toBe('high');
});

// Test 18: Pagination works correctly
test('pagination returns correct metadata', function () {
    $customer = User::factory()->customer()->create();

    Ticket::factory()->count(25)->create(['user_id' => $customer->id]);

    $token = $customer->createToken('Test', ['tickets:view'])->plainTextToken;

    $response = $this->withToken($token)
        ->getJson('/api/v1/tickets?per_page=10&page=2');

    $response->assertStatus(200);

    $json = $response->json();

    expect($json)->toHaveKey('data');
    expect($json)->toHaveKey('meta');
    expect($json)->toHaveKey('links');

    expect($json['meta'])->toHaveKey('current_page');
    expect($json['meta'])->toHaveKey('per_page');
    expect($json['meta'])->toHaveKey('total');
    expect($json['meta']['total'])->toBeGreaterThanOrEqual(25);
});
