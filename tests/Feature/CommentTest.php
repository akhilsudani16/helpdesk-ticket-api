<?php

use App\Models\Ticket;
use App\Models\TicketComment;
use App\Models\User;
use App\Permissions\V1\Abilities;

// Priority 5: Comment Tests

test('customer can create public comment on own ticket', function () {
    $customer = User::factory()->customer()->create();
    $ticket = Ticket::factory()->create(['user_id' => $customer->id]);
    
    $token = $customer->createToken('Test', Abilities::getAbilities($customer))->plainTextToken;

    $response = $this->withToken($token)
        ->postJson("/api/v1/tickets/{$ticket->id}/comments", [
            'body' => 'This is a public comment from the customer.',
        ]);

    $response->assertStatus(201)
        ->assertJson([
            'status' => 'success',
            'message' => 'Comment created successfully.',
        ]);

    $this->assertDatabaseHas('ticket_comments', [
        'ticket_id' => $ticket->id,
        'user_id' => $customer->id,
        'is_internal' => false,
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
            'body' => 'This is an internal note.',
            'is_internal' => true,
        ]);

    $response->assertStatus(201);

    $this->assertDatabaseHas('ticket_comments', [
        'ticket_id' => $ticket->id,
        'is_internal' => true,
    ]);
});

test('customer cannot create internal comment', function () {
    $customer = User::factory()->customer()->create();
    $ticket = Ticket::factory()->create(['user_id' => $customer->id]);
    
    $token = $customer->createToken('Test', Abilities::getAbilities($customer))->plainTextToken;

    $response = $this->withToken($token)
        ->postJson("/api/v1/tickets/{$ticket->id}/comments", [
            'body' => 'Trying to create internal comment.',
            'is_internal' => true,
        ]);

    // Customers attempting to create internal comments should get validation error
    $response->assertStatus(422)
        ->assertJson([
            'status' => 'error',
            'message' => 'Validation failed.',
        ]);
    
    // Verify no comment was created
    $this->assertDatabaseMissing('ticket_comments', [
        'ticket_id' => $ticket->id,
        'body' => 'Trying to create internal comment.',
    ]);
});

test('customer cannot see internal comments', function () {
    $customer = User::factory()->customer()->create();
    $agent = User::factory()->agent()->create();
    $ticket = Ticket::factory()->create(['user_id' => $customer->id]);
    
    TicketComment::factory()->create([
        'ticket_id' => $ticket->id,
        'user_id' => $agent->id,
        'is_internal' => true,
        'body' => 'Internal note',
    ]);
    
    TicketComment::factory()->create([
        'ticket_id' => $ticket->id,
        'user_id' => $customer->id,
        'is_internal' => false,
        'body' => 'Public comment',
    ]);
    
    $token = $customer->createToken('Test', Abilities::getAbilities($customer))->plainTextToken;

    $response = $this->withToken($token)
        ->getJson("/api/v1/tickets/{$ticket->id}/comments");

    $response->assertStatus(200);
    
    $comments = $response->json('data');
    expect($comments)->toHaveCount(1);
    expect($comments[0]['body'])->toBe('Public comment');
});

test('agent can see internal comments', function () {
    $customer = User::factory()->customer()->create();
    $agent = User::factory()->agent()->create();
    $ticket = Ticket::factory()->create([
        'user_id' => $customer->id,
        'assigned_to' => $agent->id,
    ]);
    
    TicketComment::factory()->create([
        'ticket_id' => $ticket->id,
        'user_id' => $agent->id,
        'is_internal' => true,
        'body' => 'Internal note',
    ]);
    
    $token = $agent->createToken('Test', Abilities::getAbilities($agent))->plainTextToken;

    $response = $this->withToken($token)
        ->getJson("/api/v1/tickets/{$ticket->id}/comments");

    $response->assertStatus(200);
    
    $comments = $response->json('data');
    expect($comments)->toHaveCount(1);
    expect($comments[0]['is_internal'])->toBeTrue();
});

test('customer cannot comment on other customer ticket', function () {
    $customer1 = User::factory()->customer()->create();
    $customer2 = User::factory()->customer()->create();
    $ticket = Ticket::factory()->create(['user_id' => $customer1->id]);
    
    $token = $customer2->createToken('Test', Abilities::getAbilities($customer2))->plainTextToken;

    $response = $this->withToken($token)
        ->postJson("/api/v1/tickets/{$ticket->id}/comments", [
            'body' => 'Unauthorized comment.',
        ]);

    $response->assertStatus(403);
});
