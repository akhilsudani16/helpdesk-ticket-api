<?php

use App\Models\Ticket;
use App\Models\User;

test('unauthenticated user cannot list tickets', function () {
    $response = $this->getJson('/api/v1/tickets');

    $response->assertStatus(401)
        ->assertJson([
            'status' => 'error',
            'message' => 'Unauthenticated.',
        ]);
});

test('customer can create own ticket', function () {
    $customer = User::factory()->customer()->create();
    $token = $customer->createToken('Test', ['tickets:view', 'tickets:create'])->plainTextToken;

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
    ]);
});

test('customer cannot view another customer ticket', function () {
    $customer1 = User::factory()->customer()->create();
    $customer2 = User::factory()->customer()->create();
    
    $ticket = Ticket::factory()->create(['user_id' => $customer1->id]);
    
    $token = $customer2->createToken('Test', ['tickets:view'])->plainTextToken;

    $response = $this->withToken($token)
        ->getJson("/api/v1/tickets/{$ticket->id}");

    $response->assertStatus(403);
});

test('admin can view all tickets', function () {
    $admin = User::factory()->admin()->create();
    $customer = User::factory()->customer()->create();
    
    $ticket = Ticket::factory()->create(['user_id' => $customer->id]);
    
    $token = $admin->createToken('Test', ['tickets:view'])->plainTextToken;

    $response = $this->withToken($token)
        ->getJson("/api/v1/tickets/{$ticket->id}");

    $response->assertStatus(200)
        ->assertJson([
            'status' => 'success',
        ]);
});

test('customer cannot update status', function () {
    $customer = User::factory()->customer()->create();
    $ticket = Ticket::factory()->create(['user_id' => $customer->id, 'status' => 'open']);
    
    $token = $customer->createToken('Test', ['tickets:view', 'tickets:update'])->plainTextToken;

    $response = $this->withToken($token)
        ->patchJson("/api/v1/tickets/{$ticket->id}", [
            'status' => 'closed',
        ]);

    // The status field should be ignored/removed for customers
    $ticket->refresh();
    expect($ticket->status)->toBe('open');
});

test('agent can update assigned ticket', function () {
    $agent = User::factory()->agent()->create();
    $customer = User::factory()->customer()->create();
    
    $ticket = Ticket::factory()->create([
        'user_id' => $customer->id,
        'assigned_to' => $agent->id,
        'status' => 'open',
    ]);
    
    $token = $agent->createToken('Test', ['tickets:view', 'tickets:update'])->plainTextToken;

    $response = $this->withToken($token)
        ->patchJson("/api/v1/tickets/{$ticket->id}", [
            'status' => 'in_progress',
        ]);

    $response->assertStatus(200);
    
    $ticket->refresh();
    expect($ticket->status)->toBe('in_progress');
});

test('agent cannot update unassigned ticket', function () {
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

test('customer can delete own open ticket', function () {
    $customer = User::factory()->customer()->create();
    $ticket = Ticket::factory()->create([
        'user_id' => $customer->id,
        'status' => 'open',
    ]);
    
    $token = $customer->createToken('Test', ['tickets:view', 'tickets:delete'])->plainTextToken;

    $response = $this->withToken($token)
        ->deleteJson("/api/v1/tickets/{$ticket->id}");

    $response->assertStatus(200);
    
    $this->assertSoftDeleted('tickets', ['id' => $ticket->id]);
});

test('customer cannot delete closed ticket', function () {
    $customer = User::factory()->customer()->create();
    $ticket = Ticket::factory()->create([
        'user_id' => $customer->id,
        'status' => 'closed',
    ]);
    
    $token = $customer->createToken('Test', ['tickets:view', 'tickets:delete'])->plainTextToken;

    $response = $this->withToken($token)
        ->deleteJson("/api/v1/tickets/{$ticket->id}");

    $response->assertStatus(403);
});

test('include parameter loads comments', function () {
    $customer = User::factory()->customer()->create();
    $ticket = Ticket::factory()->create(['user_id' => $customer->id]);
    $ticket->comments()->create([
        'user_id' => $customer->id,
        'body' => 'Test comment',
        'is_internal' => false,
    ]);
    
    $token = $customer->createToken('Test', ['tickets:view'])->plainTextToken;

    $response = $this->withToken($token)
        ->getJson("/api/v1/tickets/{$ticket->id}?include=comments");

    $response->assertStatus(200);
    
    // The response has structure: { "status": "success", "data": { ...ticket resource... } }
    // And the ticket resource should have comments when include=comments is used
    $json = $response->json();
    expect($json['status'])->toBe('success');
    expect($json['data'])->toHaveKey('comments');
});

test('filtering by status works', function () {
    $customer = User::factory()->customer()->create();
    Ticket::factory()->create(['user_id' => $customer->id, 'status' => 'open']);
    Ticket::factory()->create(['user_id' => $customer->id, 'status' => 'closed']);
    
    $token = $customer->createToken('Test', ['tickets:view'])->plainTextToken;

    $response = $this->withToken($token)
        ->getJson('/api/v1/tickets?filter[status]=open');

    $response->assertStatus(200);
    
    $data = $response->json('data');
    foreach ($data as $ticket) {
        expect($ticket['status'])->toBe('open');
    }
});

test('sorting descending by created_at works', function () {
    $customer = User::factory()->customer()->create();
    $ticket1 = Ticket::factory()->create(['user_id' => $customer->id, 'created_at' => now()->subDays(2)]);
    $ticket2 = Ticket::factory()->create(['user_id' => $customer->id, 'created_at' => now()->subDay()]);
    $ticket3 = Ticket::factory()->create(['user_id' => $customer->id, 'created_at' => now()]);
    
    $token = $customer->createToken('Test', ['tickets:view'])->plainTextToken;

    $response = $this->withToken($token)
        ->getJson('/api/v1/tickets?sort=-created_at');

    $response->assertStatus(200);
    
    $data = $response->json('data');
    expect($data[0]['id'])->toBe($ticket3->id);
});

test('unsupported sort field returns 400', function () {
    $customer = User::factory()->customer()->create();
    $token = $customer->createToken('Test', ['tickets:view'])->plainTextToken;

    $response = $this->withToken($token)
        ->getJson('/api/v1/tickets?sort=invalid_field');

    $response->assertStatus(400);
});

test('unsupported filter returns 400', function () {
    $customer = User::factory()->customer()->create();
    $token = $customer->createToken('Test', ['tickets:view'])->plainTextToken;

    $response = $this->withToken($token)
        ->getJson('/api/v1/tickets?filter[invalid_field]=value');

    $response->assertStatus(400)
        ->assertJson([
            'status' => 'error',
        ]);
});

test('invalid status filter value returns 400', function () {
    $customer = User::factory()->customer()->create();
    $token = $customer->createToken('Test', ['tickets:view'])->plainTextToken;

    $response = $this->withToken($token)
        ->getJson('/api/v1/tickets?filter[status]=invalid_status');

    $response->assertStatus(400);
});

test('invalid date format for created_after returns 400', function () {
    $customer = User::factory()->customer()->create();
    $token = $customer->createToken('Test', ['tickets:view'])->plainTextToken;

    $response = $this->withToken($token)
        ->getJson('/api/v1/tickets?filter[created_after]=invalid-date');

    $response->assertStatus(400);
});

test('customer cannot send forbidden fields in update request', function () {
    $customer = User::factory()->customer()->create();
    $ticket = Ticket::factory()->create(['user_id' => $customer->id, 'status' => 'open']);
    
    $token = $customer->createToken('Test', ['tickets:view', 'tickets:update'])->plainTextToken;

    $response = $this->withToken($token)
        ->patchJson("/api/v1/tickets/{$ticket->id}", [
            'title' => 'Updated title for testing validation',
            'status' => 'closed', // This should be ignored
            'priority' => 'urgent', // This should be ignored
            'assigned_to' => 1, // This should be ignored
        ]);

    $response->assertStatus(200);
    
    // Verify forbidden fields were not updated
    $ticket->refresh();
    expect($ticket->status)->toBe('open');
    expect($ticket->title)->toBe('Updated title for testing validation');
});
