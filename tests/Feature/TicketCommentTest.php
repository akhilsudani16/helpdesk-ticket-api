<?php

use App\Models\Ticket;
use App\Models\TicketComment;
use App\Models\User;
use App\Permissions\V1\Abilities;

test('customer can create public comment', function () {
    $customer = User::factory()->customer()->create();
    $ticket = Ticket::factory()->create(['user_id' => $customer->id]);
    
    $token = $customer->createToken('Test', [Abilities::ViewTickets, Abilities::ViewComments, Abilities::CreateComment])->plainTextToken;

    $response = $this->withToken($token)
        ->postJson("/api/v1/tickets/{$ticket->id}/comments", [
            'body' => 'This is a test comment.',
        ]);

    $response->assertStatus(201)
        ->assertJson([
            'status' => 'success',
            'message' => 'Comment created successfully.',
        ]);

    $this->assertDatabaseHas('ticket_comments', [
        'ticket_id' => $ticket->id,
        'user_id' => $customer->id,
        'body' => 'This is a test comment.',
        'is_internal' => false,
    ]);
});

test('customer cannot create internal comment', function () {
    $customer = User::factory()->customer()->create();
    $ticket = Ticket::factory()->create(['user_id' => $customer->id]);
    
    $token = $customer->createToken('Test', [Abilities::ViewTickets, Abilities::ViewComments, Abilities::CreateComment])->plainTextToken;

    $response = $this->withToken($token)
        ->postJson("/api/v1/tickets/{$ticket->id}/comments", [
            'body' => 'This is a test comment.',
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
        'body' => 'This is a test comment.',
    ]);
});

test('agent can create internal comment', function () {
    $agent = User::factory()->agent()->create();
    $customer = User::factory()->customer()->create();
    $ticket = Ticket::factory()->create(['user_id' => $customer->id, 'assigned_to' => $agent->id]);
    
    $token = $agent->createToken('Test', [
        Abilities::ViewTickets,
        Abilities::ViewComments,
        Abilities::CreateComment,
        Abilities::CreateInternalComment
    ])->plainTextToken;

    $response = $this->withToken($token)
        ->postJson("/api/v1/tickets/{$ticket->id}/comments", [
            'body' => 'Internal note for the team.',
            'is_internal' => true,
        ]);

    $response->assertStatus(201);

    $this->assertDatabaseHas('ticket_comments', [
        'ticket_id' => $ticket->id,
        'user_id' => $agent->id,
        'is_internal' => true,
    ]);
});

test('customer cannot see internal comments', function () {
    $customer = User::factory()->customer()->create();
    $agent = User::factory()->agent()->create();
    $ticket = Ticket::factory()->create(['user_id' => $customer->id]);
    
    // Create public comment
    TicketComment::factory()->create([
        'ticket_id' => $ticket->id,
        'user_id' => $customer->id,
        'is_internal' => false,
    ]);
    
    // Create internal comment
    TicketComment::factory()->create([
        'ticket_id' => $ticket->id,
        'user_id' => $agent->id,
        'is_internal' => true,
    ]);
    
    $token = $customer->createToken('Test', [Abilities::ViewTickets, Abilities::ViewComments])->plainTextToken;

    $response = $this->withToken($token)
        ->getJson("/api/v1/tickets/{$ticket->id}/comments");

    $response->assertStatus(200);
    
    $comments = $response->json('data');
    // Customer should only see 1 comment (the public one)
    expect(count($comments))->toBe(1);
});

test('agent can see internal comments', function () {
    $customer = User::factory()->customer()->create();
    $agent = User::factory()->agent()->create();
    $ticket = Ticket::factory()->create(['user_id' => $customer->id, 'assigned_to' => $agent->id]);
    
    // Create public comment
    TicketComment::factory()->create([
        'ticket_id' => $ticket->id,
        'user_id' => $customer->id,
        'is_internal' => false,
    ]);
    
    // Create internal comment
    TicketComment::factory()->create([
        'ticket_id' => $ticket->id,
        'user_id' => $agent->id,
        'is_internal' => true,
    ]);
    
    $token = $agent->createToken('Test', [Abilities::ViewTickets, Abilities::ViewComments])->plainTextToken;

    $response = $this->withToken($token)
        ->getJson("/api/v1/tickets/{$ticket->id}/comments");

    $response->assertStatus(200);
    
    $comments = $response->json('data');
    expect(count($comments))->toBe(2);
});

