<?php

use App\Models\Ticket;
use App\Models\TicketComment;
use App\Models\User;
use App\Permissions\V1\Abilities;

// Priority 6: Policy Verification Tests

// TicketPolicy Tests

test('ticket policy ownership check works for customer', function () {
    $customer1 = User::factory()->customer()->create();
    $customer2 = User::factory()->customer()->create();
    $ticket = Ticket::factory()->create(['user_id' => $customer1->id]);
    
    // Test through actual HTTP requests
    $token1 = $customer1->createToken('Test', Abilities::getAbilities($customer1))->plainTextToken;
    
    // Customer1 can view their own ticket
    $response = $this->withToken($token1)->getJson("/api/v1/tickets/{$ticket->id}");
    $response->assertStatus(200);
    
    // IMPORTANT: Flush the authenticated user to avoid caching
    $this->app->forgetInstance('auth');
    auth()->forgetGuards();
    
    // Create a fresh request for customer2
    $token2 = $customer2->createToken('Test2', Abilities::getAbilities($customer2))->plainTextToken;
    
    // Customer2 cannot view customer1's ticket
    $response = $this->withToken($token2)->getJson("/api/v1/tickets/{$ticket->id}");
    $response->assertStatus(403);
});

test('ticket policy role restriction for agent', function () {
    $agent = User::factory()->agent()->create();
    $customer = User::factory()->customer()->create();
    
    $assignedTicket = Ticket::factory()->create([
        'user_id' => $customer->id,
        'assigned_to' => $agent->id,
    ]);
    
    $unassignedTicket = Ticket::factory()->create([
        'user_id' => $customer->id,
        'assigned_to' => null,
    ]);
    
    $token = $agent->createToken('Test', Abilities::getAbilities($agent))->plainTextToken;
    
    // Agent can view assigned ticket
    $response = $this->withToken($token)->getJson("/api/v1/tickets/{$assignedTicket->id}");
    $response->assertStatus(200);
    
    // Agent cannot view unassigned ticket
    $response = $this->withToken($token)->getJson("/api/v1/tickets/{$unassignedTicket->id}");
    $response->assertStatus(403);
    
    // Agent can update assigned ticket
    $response = $this->withToken($token)->patchJson("/api/v1/tickets/{$assignedTicket->id}", [
        'status' => 'in_progress',
    ]);
    $response->assertStatus(200);
    
    // Agent cannot update unassigned ticket
    $response = $this->withToken($token)->patchJson("/api/v1/tickets/{$unassignedTicket->id}", [
        'status' => 'in_progress',
    ]);
    $response->assertStatus(403);
});

test('ticket policy admin override works', function () {
    $admin = User::factory()->admin()->create();
    $customer = User::factory()->customer()->create();
    $ticket = Ticket::factory()->create(['user_id' => $customer->id]);
    
    $token = $admin->createToken('Test', Abilities::getAbilities($admin))->plainTextToken;
    
    // Admin can view any ticket
    $response = $this->withToken($token)->getJson("/api/v1/tickets/{$ticket->id}");
    $response->assertStatus(200);
    
    // Admin can update any ticket
    $response = $this->withToken($token)->patchJson("/api/v1/tickets/{$ticket->id}", [
        'status' => 'resolved',
    ]);
    $response->assertStatus(200);
    
    // Admin can delete any ticket
    $response = $this->withToken($token)->deleteJson("/api/v1/tickets/{$ticket->id}");
    $response->assertStatus(200);
});

test('ticket policy delete restriction for customer', function () {
    $customer = User::factory()->customer()->create();
    $openTicket = Ticket::factory()->open()->create(['user_id' => $customer->id]);
    $closedTicket = Ticket::factory()->closed()->create(['user_id' => $customer->id]);
    
    $token = $customer->createToken('Test', Abilities::getAbilities($customer))->plainTextToken;
    
    // Customer can delete open ticket
    $response = $this->withToken($token)->deleteJson("/api/v1/tickets/{$openTicket->id}");
    $response->assertStatus(200);
    
    // Customer cannot delete closed ticket
    $response = $this->withToken($token)->deleteJson("/api/v1/tickets/{$closedTicket->id}");
    $response->assertStatus(403);
});

test('ticket policy agent cannot delete', function () {
    $agent = User::factory()->agent()->create();
    $customer = User::factory()->customer()->create();
    $ticket = Ticket::factory()->create([
        'user_id' => $customer->id,
        'assigned_to' => $agent->id,
    ]);
    
    expect($agent->can('delete', $ticket))->toBeFalse();
});

// TicketCommentPolicy Tests

test('comment policy requires ticket access', function () {
    $customer1 = User::factory()->customer()->create();
    $customer2 = User::factory()->customer()->create();
    $ticket = Ticket::factory()->create(['user_id' => $customer1->id]);
    
    $token1 = $customer1->createToken('Test', Abilities::getAbilities($customer1))->plainTextToken;
    
    // Customer1 can create comment on their ticket
    $response = $this->withToken($token1)->postJson("/api/v1/tickets/{$ticket->id}/comments", [
        'body' => 'Test comment from owner',
    ]);
    $response->assertStatus(201);
    expect($response->json('status'))->toBe('success');
    
    // Flush auth to avoid caching
    $this->app->forgetInstance('auth');
    auth()->forgetGuards();
    
    $token2 = $customer2->createToken('Test2', Abilities::getAbilities($customer2))->plainTextToken;
    
    // Customer2 cannot create comment on customer1's ticket
    $response = $this->withToken($token2)->postJson("/api/v1/tickets/{$ticket->id}/comments", [
        'body' => 'Unauthorized comment',
    ]);
    $response->assertStatus(403);
    expect($response->json('status'))->toBe('error');
});

test('comment policy internal comment restriction', function () {
    $customer = User::factory()->customer()->create();
    $agent = User::factory()->agent()->create();
    $admin = User::factory()->admin()->create();
    
    $customerTicket = Ticket::factory()->create(['user_id' => $customer->id]);
    $agentTicket = Ticket::factory()->create(['user_id' => $customer->id, 'assigned_to' => $agent->id]);
    $adminTicket = Ticket::factory()->create(['user_id' => $customer->id]);
    
    // Customer cannot create internal comment (validation error)
    $tokenCustomer = $customer->createToken('Test', Abilities::getAbilities($customer))->plainTextToken;
    $response = $this->withToken($tokenCustomer)->postJson("/api/v1/tickets/{$customerTicket->id}/comments", [
        'body' => 'Test comment',
        'is_internal' => true,
    ]);
    $response->assertStatus(422); // Validation error
    
    // Flush auth
    $this->app->forgetInstance('auth');
    auth()->forgetGuards();
    
    // Agent can create internal comment
    $tokenAgent = $agent->createToken('Test', Abilities::getAbilities($agent))->plainTextToken;
    $response = $this->withToken($tokenAgent)->postJson("/api/v1/tickets/{$agentTicket->id}/comments", [
        'body' => 'Internal note',
        'is_internal' => true,
    ]);
    $response->assertStatus(201);
    $comment = TicketComment::where('ticket_id', $agentTicket->id)->where('body', 'Internal note')->first();
    expect($comment->is_internal)->toBeTrue(); // Should be true
    
    // Flush auth
    $this->app->forgetInstance('auth');
    auth()->forgetGuards();
    
    // Admin can create internal comment
    $tokenAdmin = $admin->createToken('Test', Abilities::getAbilities($admin))->plainTextToken;
    $response = $this->withToken($tokenAdmin)->postJson("/api/v1/tickets/{$adminTicket->id}/comments", [
        'body' => 'Admin internal note',
        'is_internal' => true,
    ]);
    $response->assertStatus(201);
    $comment = TicketComment::where('ticket_id', $adminTicket->id)->where('body', 'Admin internal note')->first();
    expect($comment->is_internal)->toBeTrue(); // Should be true
});

test('comment policy view restriction for internal comments', function () {
    $customer = User::factory()->customer()->create();
    $agent = User::factory()->agent()->create();
    $ticket = Ticket::factory()->create(['user_id' => $customer->id, 'assigned_to' => $agent->id]);
    
    // Create comments directly
    TicketComment::create([
        'ticket_id' => $ticket->id,
        'user_id' => $agent->id,
        'body' => 'Public comment',
        'is_internal' => false,
    ]);
    
    TicketComment::create([
        'ticket_id' => $ticket->id,
        'user_id' => $agent->id,
        'body' => 'Internal comment',
        'is_internal' => true,
    ]);
    
    // Customer can see public comments but not internal
    $tokenCustomer = $customer->createToken('Test', Abilities::getAbilities($customer))->plainTextToken;
    $response = $this->withToken($tokenCustomer)->getJson("/api/v1/tickets/{$ticket->id}/comments");
    $response->assertStatus(200);
    $comments = $response->json('data');
    expect($comments)->toHaveCount(1); // Only public comment
    expect($comments[0]['body'])->toBe('Public comment');
    
    // Flush auth
    $this->app->forgetInstance('auth');
    auth()->forgetGuards();
    
    // Agent can see all comments including internal
    $tokenAgent = $agent->createToken('Test', Abilities::getAbilities($agent))->plainTextToken;
    $response = $this->withToken($tokenAgent)->getJson("/api/v1/tickets/{$ticket->id}/comments");
    $response->assertStatus(200);
    $comments = $response->json('data');
    expect($comments)->toHaveCount(2); // Both comments
});

// UserPolicy Tests

test('user policy view restriction for customers', function () {
    $customer = User::factory()->customer()->create();
    $otherUser = User::factory()->create();
    
    expect($customer->can('viewAny', User::class))->toBeFalse();
    expect($customer->can('view', $otherUser))->toBeFalse();
});

test('user policy view access for agents and admins', function () {
    $agent = User::factory()->agent()->create();
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();
    
    // Agent can view users
    $tokenAgent = $agent->createToken('Test', Abilities::getAbilities($agent))->plainTextToken;
    $response = $this->withToken($tokenAgent)->getJson('/api/v1/users');
    $response->assertStatus(200);
    
    $response = $this->withToken($tokenAgent)->getJson("/api/v1/users/{$user->id}");
    $response->assertStatus(200);
    
    // Admin can view users
    $tokenAdmin = $admin->createToken('Test', Abilities::getAbilities($admin))->plainTextToken;
    $response = $this->withToken($tokenAdmin)->getJson('/api/v1/users');
    $response->assertStatus(200);
    
    $response = $this->withToken($tokenAdmin)->getJson("/api/v1/users/{$user->id}");
    $response->assertStatus(200);
});

test('user policy manage restriction', function () {
    $customer = User::factory()->customer()->create();
    $agent = User::factory()->agent()->create();
    $admin = User::factory()->admin()->create();
    
    // Create some users to list
    User::factory()->count(3)->create();
    
    // Customer cannot access users endpoint
    $tokenCustomer = $customer->createToken('Test', Abilities::getAbilities($customer))->plainTextToken;
    $response = $this->withToken($tokenCustomer)->getJson('/api/v1/users');
    $response->assertStatus(403);
    expect($response->json('status'))->toBe('error');
    
    // Flush auth
    $this->app->forgetInstance('auth');
    auth()->forgetGuards();
    
    // Agent can view users
    $tokenAgent = $agent->createToken('Test', Abilities::getAbilities($agent))->plainTextToken;
    $response = $this->withToken($tokenAgent)->getJson('/api/v1/users');
    $response->assertStatus(200);
    expect($response->json('status'))->toBe('success');
    
    // Flush auth
    $this->app->forgetInstance('auth');
    auth()->forgetGuards();
    
    // Admin can view and manage users
    $tokenAdmin = $admin->createToken('Test', Abilities::getAbilities($admin))->plainTextToken;
    $response = $this->withToken($tokenAdmin)->getJson('/api/v1/users');
    $response->assertStatus(200);
    expect($response->json('status'))->toBe('success');
});
