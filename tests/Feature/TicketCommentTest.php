<?php

use App\Models\Ticket;
use App\Models\TicketComment;
use App\Models\User;

test('customer can create public comment', function () {
    $customer = User::factory()->customer()->create();
    $ticket = Ticket::factory()->create(['user_id' => $customer->id]);

    $token = $customer->createToken('Test', ['tickets:view', 'comments:view', 'comments:create'])->plainTextToken;

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

    $token = $customer->createToken('Test', ['tickets:view', 'comments:view', 'comments:create'])->plainTextToken;

    $response = $this->withToken($token)
        ->postJson("/api/v1/tickets/{$ticket->id}/comments", [
            'body' => 'This is a test comment.',
            'is_internal' => true,
        ]);


    $response->assertStatus(201);


    $this->assertDatabaseHas('ticket_comments', [
        'ticket_id' => $ticket->id,
        'user_id' => $customer->id,
        'body' => 'This is a test comment.',
        'is_internal' => false,
    ]);
});

test('agent can create internal comment', function () {
    $agent = User::factory()->agent()->create();
    $customer = User::factory()->customer()->create();
    $ticket = Ticket::factory()->create(['user_id' => $customer->id, 'assigned_to' => $agent->id]);

    $token = $agent->createToken('Test', [
        'tickets:view',
        'comments:view',
        'comments:create',
        'comments:create-internal'
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


    TicketComment::factory()->create([
        'ticket_id' => $ticket->id,
        'user_id' => $customer->id,
        'is_internal' => false,
    ]);


    TicketComment::factory()->create([
        'ticket_id' => $ticket->id,
        'user_id' => $agent->id,
        'is_internal' => true,
    ]);

    $token = $customer->createToken('Test', ['tickets:view', 'comments:view'])->plainTextToken;

    $response = $this->withToken($token)
        ->getJson("/api/v1/tickets/{$ticket->id}/comments");

    $response->assertStatus(200);

    $comments = $response->json('data');

    expect(count($comments))->toBe(1);
});

test('agent can see internal comments', function () {
    $customer = User::factory()->customer()->create();
    $agent = User::factory()->agent()->create();
    $ticket = Ticket::factory()->create(['user_id' => $customer->id, 'assigned_to' => $agent->id]);


    TicketComment::factory()->create([
        'ticket_id' => $ticket->id,
        'user_id' => $customer->id,
        'is_internal' => false,
    ]);


    TicketComment::factory()->create([
        'ticket_id' => $ticket->id,
        'user_id' => $agent->id,
        'is_internal' => true,
    ]);

    $token = $agent->createToken('Test', ['tickets:view', 'comments:view'])->plainTextToken;

    $response = $this->withToken($token)
        ->getJson("/api/v1/tickets/{$ticket->id}/comments");

    $response->assertStatus(200);

    $comments = $response->json('data');
    expect(count($comments))->toBe(2);
});
