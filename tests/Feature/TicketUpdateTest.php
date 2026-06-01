<?php

use App\Models\Ticket;
use App\Models\User;
use App\Permissions\V1\Abilities;

// Priority 5: Ticket Update Tests

test('customer cannot update status', function () {
    $customer = User::factory()->customer()->create();
    $ticket = Ticket::factory()->create(['user_id' => $customer->id, 'status' => 'open']);
    
    $token = $customer->createToken('Test', Abilities::getAbilities($customer))->plainTextToken;

    $response = $this->withToken($token)
        ->patchJson("/api/v1/tickets/{$ticket->id}", [
            'status' => 'closed',
        ]);

    // The status field should be ignored/removed for customers
    $ticket->refresh();
    expect($ticket->status->value)->toBe('open');
});

test('agent can update assigned ticket', function () {
    $agent = User::factory()->agent()->create();
    $customer = User::factory()->customer()->create();
    
    $ticket = Ticket::factory()->create([
        'user_id' => $customer->id,
        'assigned_to' => $agent->id,
        'status' => 'open',
    ]);
    
    $token = $agent->createToken('Test', Abilities::getAbilities($agent))->plainTextToken;

    $response = $this->withToken($token)
        ->patchJson("/api/v1/tickets/{$ticket->id}", [
            'status' => 'in_progress',
        ]);

    $response->assertStatus(200);
    
    $ticket->refresh();
    expect($ticket->status->value)->toBe('in_progress');
});

test('agent cannot update unassigned ticket', function () {
    $agent = User::factory()->agent()->create();
    $customer = User::factory()->customer()->create();
    
    $ticket = Ticket::factory()->create([
        'user_id' => $customer->id,
        'assigned_to' => null,
    ]);
    
    $token = $agent->createToken('Test', Abilities::getAbilities($agent))->plainTextToken;

    $response = $this->withToken($token)
        ->patchJson("/api/v1/tickets/{$ticket->id}", [
            'status' => 'in_progress',
        ]);

    $response->assertStatus(403);
});

test('admin can update any ticket', function () {
    $admin = User::factory()->admin()->create();
    $customer = User::factory()->customer()->create();
    
    $ticket = Ticket::factory()->create([
        'user_id' => $customer->id,
        'status' => 'open',
    ]);
    
    $token = $admin->createToken('Test', Abilities::getAbilities($admin))->plainTextToken;

    $response = $this->withToken($token)
        ->patchJson("/api/v1/tickets/{$ticket->id}", [
            'status' => 'resolved',
            'priority' => 'urgent',
    ]);

    $response->assertStatus(200);
    
    $ticket->refresh();
    expect($ticket->status->value)->toBe('resolved');
    expect($ticket->priority->value)->toBe('urgent');
});

test('customer can update own ticket title and description', function () {
    $customer = User::factory()->customer()->create();
    $ticket = Ticket::factory()->create(['user_id' => $customer->id]);
    
    $token = $customer->createToken('Test', Abilities::getAbilities($customer))->plainTextToken;

    $response = $this->withToken($token)
        ->patchJson("/api/v1/tickets/{$ticket->id}", [
            'title' => 'Updated Title',
            'description' => 'Updated description with enough characters to pass validation rules.',
        ]);

    $response->assertStatus(200);
    
    $ticket->refresh();
    expect($ticket->title)->toBe('Updated Title');
});

test('admin can assign ticket to agent', function () {
    $admin = User::factory()->admin()->create();
    $agent = User::factory()->agent()->create();
    $customer = User::factory()->customer()->create();
    
    $ticket = Ticket::factory()->create([
        'user_id' => $customer->id,
        'assigned_to' => null,
    ]);
    
    $token = $admin->createToken('Test', Abilities::getAbilities($admin))->plainTextToken;

    $response = $this->withToken($token)
        ->patchJson("/api/v1/tickets/{$ticket->id}", [
            'assigned_to' => $agent->id,
        ]);

    $response->assertStatus(200);
    
    $ticket->refresh();
    expect($ticket->assigned_to)->toBe($agent->id);
});

test('customer cannot assign ticket', function () {
    $customer = User::factory()->customer()->create();
    $agent = User::factory()->agent()->create();
    $ticket = Ticket::factory()->create(['user_id' => $customer->id]);
    
    $token = $customer->createToken('Test', Abilities::getAbilities($customer))->plainTextToken;

    $response = $this->withToken($token)
        ->patchJson("/api/v1/tickets/{$ticket->id}", [
            'assigned_to' => $agent->id,
        ]);

    $ticket->refresh();
    expect($ticket->assigned_to)->toBeNull();
});
