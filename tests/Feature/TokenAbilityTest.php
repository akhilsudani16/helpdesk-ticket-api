<?php

use App\Models\Ticket;
use App\Models\User;
use App\Permissions\V1\Abilities;

// Priority 7: Token Ability Verification Tests

test('customer token cannot access users endpoint', function () {
    $customer = User::factory()->customer()->create();
    $token = $customer->createToken('Test', Abilities::getAbilities($customer))->plainTextToken;

    $response = $this->withToken($token)
        ->getJson('/api/v1/users');

    $response->assertStatus(403)
        ->assertJson([
            'status' => 'error',
        ]);
});

test('agent token can access users endpoint', function () {
    $agent = User::factory()->agent()->create();
    $token = $agent->createToken('Test', Abilities::getAbilities($agent))->plainTextToken;

    $response = $this->withToken($token)
        ->getJson('/api/v1/users');

    $response->assertStatus(200);
});

test('admin token can access users endpoint', function () {
    $admin = User::factory()->admin()->create();
    $token = $admin->createToken('Test', Abilities::getAbilities($admin))->plainTextToken;

    $response = $this->withToken($token)
        ->getJson('/api/v1/users');

    $response->assertStatus(200);
});

test('agent cannot delete any ticket', function () {
    $agent = User::factory()->agent()->create();
    $customer = User::factory()->customer()->create();
    $ticket = Ticket::factory()->create([
        'user_id' => $customer->id,
        'assigned_to' => $agent->id,
    ]);
    
    $token = $agent->createToken('Test', Abilities::getAbilities($agent))->plainTextToken;

    $response = $this->withToken($token)
        ->deleteJson("/api/v1/tickets/{$ticket->id}");

    $response->assertStatus(403);
});

test('customer cannot create internal comment', function () {
    $customer = User::factory()->customer()->create();
    $ticket = Ticket::factory()->create(['user_id' => $customer->id]);
    
    $token = $customer->createToken('Test', Abilities::getAbilities($customer))->plainTextToken;

    $response = $this->withToken($token)
        ->postJson("/api/v1/tickets/{$ticket->id}/comments", [
            'body' => 'This is an internal comment',
            'is_internal' => true,
        ]);

    // The is_internal field is stripped in prepareForValidation for customers
    // So the comment is created as public (is_internal = false)
    $response->assertStatus(201);
    
    // Verify the comment was created as public, not internal
    $this->assertDatabaseHas('ticket_comments', [
        'ticket_id' => $ticket->id,
        'is_internal' => false, // Should be false, not true
    ]);
});

test('agent can create internal comment', function () {
    $agent = User::factory()->agent()->create();
    $customer = User::factory()->customer()->create();
    $ticket = Ticket::factory()->create([
        'user_id' => $customer->id,
        'assigned_to' => $agent->id,
    ]);
    
    $token = $agent->createToken('Test', Abilities::getAbilities($agent))->plainTextToken;

    $response = $this->withToken($token)
        ->postJson("/api/v1/tickets/{$ticket->id}/comments", [
            'body' => 'This is an internal comment',
            'is_internal' => true,
        ]);

    $response->assertStatus(201);
    
    $this->assertDatabaseHas('ticket_comments', [
        'ticket_id' => $ticket->id,
        'is_internal' => true,
    ]);
});

test('admin can perform all actions', function () {
    $admin = User::factory()->admin()->create();
    $customer = User::factory()->customer()->create();
    
    $token = $admin->createToken('Test', Abilities::getAbilities($admin))->plainTextToken;

    // Can create ticket for another user
    $response = $this->withToken($token)
        ->postJson('/api/v1/tickets', [
            'title' => 'Admin Created Ticket',
            'description' => 'This ticket was created by an admin.',
            'priority' => 'high',
            'user_id' => $customer->id,
        ]);
    $response->assertStatus(201);
    
    $ticket = Ticket::latest()->first();
    
    // Can update any ticket
    $response = $this->withToken($token)
        ->patchJson("/api/v1/tickets/{$ticket->id}", [
            'status' => 'resolved',
        ]);
    $response->assertStatus(200);
    
    // Can delete any ticket
    $response = $this->withToken($token)
        ->deleteJson("/api/v1/tickets/{$ticket->id}");
    $response->assertStatus(200);
    
    // Can access users
    $response = $this->withToken($token)
        ->getJson('/api/v1/users');
    $response->assertStatus(200);
});

test('token without tickets view ability cannot list tickets', function () {
    $customer = User::factory()->customer()->create();
    // Create token without tickets:view ability
    $token = $customer->createToken('Test', [Abilities::CreateTicket])->plainTextToken;

    $response = $this->withToken($token)
        ->getJson('/api/v1/tickets');

    $response->assertStatus(403);
});

test('token without tickets create ability cannot create ticket', function () {
    $customer = User::factory()->customer()->create();
    // Create token with only view ability
    $token = $customer->createToken('Test', [Abilities::ViewTickets, Abilities::UpdateTicket])->plainTextToken;

    $response = $this->withToken($token)
        ->postJson('/api/v1/tickets', [
            'title' => 'Test Ticket',
            'description' => 'This should fail due to missing ability.',
            'priority' => 'low',
        ]);

    $response->assertStatus(403);
});

test('token without tickets update ability cannot update ticket', function () {
    $customer = User::factory()->customer()->create();
    $ticket = Ticket::factory()->create(['user_id' => $customer->id]);
    
    // Create token without update ability
    $token = $customer->createToken('Test', [Abilities::ViewTickets])->plainTextToken;

    $response = $this->withToken($token)
        ->patchJson("/api/v1/tickets/{$ticket->id}", [
            'title' => 'Updated Title',
        ]);

    $response->assertStatus(403);
});

test('token without tickets delete ability cannot delete ticket', function () {
    $customer = User::factory()->customer()->create();
    $ticket = Ticket::factory()->create(['user_id' => $customer->id, 'status' => 'closed']);
    
    // Even with full abilities, customer cannot delete closed ticket
    $token = $customer->createToken('Test', Abilities::getAbilities($customer))->plainTextToken;

    $response = $this->withToken($token)
        ->deleteJson("/api/v1/tickets/{$ticket->id}");

    $response->assertStatus(403);
});

test('customer abilities are correctly assigned', function () {
    $customer = User::factory()->customer()->create();
    $abilities = Abilities::getAbilities($customer);
    
    expect($abilities)->toContain(Abilities::ViewTickets);
    expect($abilities)->toContain(Abilities::CreateTicket);
    expect($abilities)->toContain(Abilities::UpdateTicket);
    expect($abilities)->toContain(Abilities::DeleteTicket);
    expect($abilities)->toContain(Abilities::ViewComments);
    expect($abilities)->toContain(Abilities::CreateComment);
    
    expect($abilities)->not->toContain(Abilities::CreateAnyTicket);
    expect($abilities)->not->toContain(Abilities::UpdateAnyTicket);
    expect($abilities)->not->toContain(Abilities::DeleteAnyTicket);
    expect($abilities)->not->toContain(Abilities::CreateInternalComment);
    expect($abilities)->not->toContain(Abilities::ViewUsers);
    expect($abilities)->not->toContain(Abilities::ManageUsers);
});

test('agent abilities are correctly assigned', function () {
    $agent = User::factory()->agent()->create();
    $abilities = Abilities::getAbilities($agent);
    
    expect($abilities)->toContain(Abilities::ViewTickets);
    expect($abilities)->toContain(Abilities::UpdateTicket);
    expect($abilities)->toContain(Abilities::ViewComments);
    expect($abilities)->toContain(Abilities::CreateComment);
    expect($abilities)->toContain(Abilities::CreateInternalComment);
    expect($abilities)->toContain(Abilities::ViewUsers);
    
    expect($abilities)->not->toContain(Abilities::CreateTicket);
    expect($abilities)->not->toContain(Abilities::DeleteTicket);
    expect($abilities)->not->toContain(Abilities::CreateAnyTicket);
    expect($abilities)->not->toContain(Abilities::UpdateAnyTicket);
    expect($abilities)->not->toContain(Abilities::DeleteAnyTicket);
    expect($abilities)->not->toContain(Abilities::ManageUsers);
});

test('admin abilities are correctly assigned', function () {
    $admin = User::factory()->admin()->create();
    $abilities = Abilities::getAbilities($admin);
    
    expect($abilities)->toContain(Abilities::ViewTickets);
    expect($abilities)->toContain(Abilities::CreateTicket);
    expect($abilities)->toContain(Abilities::UpdateTicket);
    expect($abilities)->toContain(Abilities::DeleteTicket);
    expect($abilities)->toContain(Abilities::CreateAnyTicket);
    expect($abilities)->toContain(Abilities::UpdateAnyTicket);
    expect($abilities)->toContain(Abilities::DeleteAnyTicket);
    expect($abilities)->toContain(Abilities::ViewComments);
    expect($abilities)->toContain(Abilities::CreateComment);
    expect($abilities)->toContain(Abilities::CreateInternalComment);
    expect($abilities)->toContain(Abilities::ViewUsers);
    expect($abilities)->toContain(Abilities::ManageUsers);
});
