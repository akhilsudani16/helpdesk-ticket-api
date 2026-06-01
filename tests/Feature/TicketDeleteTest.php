<?php

use App\Models\Ticket;
use App\Models\User;
use App\Permissions\V1\Abilities;

// Priority 5: Delete Rules Tests

test('customer can delete open ticket', function () {
    $customer = User::factory()->customer()->create();
    $ticket = Ticket::factory()->open()->create([
        'user_id' => $customer->id,
    ]);
    
    $token = $customer->createToken('Test', Abilities::getAbilities($customer))->plainTextToken;

    $response = $this->withToken($token)
        ->deleteJson("/api/v1/tickets/{$ticket->id}");

    $response->assertStatus(200)
        ->assertJson([
            'status' => 'success',
            'message' => 'Ticket deleted successfully.',
        ]);
    
    $this->assertSoftDeleted('tickets', ['id' => $ticket->id]);
});

test('customer cannot delete non open ticket', function () {
    $customer = User::factory()->customer()->create();
    $ticket = Ticket::factory()->closed()->create([
        'user_id' => $customer->id,
    ]);
    
    $token = $customer->createToken('Test', Abilities::getAbilities($customer))->plainTextToken;

    $response = $this->withToken($token)
        ->deleteJson("/api/v1/tickets/{$ticket->id}");

    $response->assertStatus(403);
    
    $this->assertDatabaseHas('tickets', [
        'id' => $ticket->id,
        'deleted_at' => null,
    ]);
});

test('customer cannot delete in progress ticket', function () {
    $customer = User::factory()->customer()->create();
    $ticket = Ticket::factory()->inProgress()->create([
        'user_id' => $customer->id,
    ]);
    
    $token = $customer->createToken('Test', Abilities::getAbilities($customer))->plainTextToken;

    $response = $this->withToken($token)
        ->deleteJson("/api/v1/tickets/{$ticket->id}");

    $response->assertStatus(403);
});

test('customer cannot delete resolved ticket', function () {
    $customer = User::factory()->customer()->create();
    $ticket = Ticket::factory()->resolved()->create([
        'user_id' => $customer->id,
    ]);
    
    $token = $customer->createToken('Test', Abilities::getAbilities($customer))->plainTextToken;

    $response = $this->withToken($token)
        ->deleteJson("/api/v1/tickets/{$ticket->id}");

    $response->assertStatus(403);
});

test('agent cannot delete ticket', function () {
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

test('admin can delete ticket', function () {
    $admin = User::factory()->admin()->create();
    $customer = User::factory()->customer()->create();
    
    $ticket = Ticket::factory()->closed()->create([
        'user_id' => $customer->id,
    ]);
    
    $token = $admin->createToken('Test', Abilities::getAbilities($admin))->plainTextToken;

    $response = $this->withToken($token)
        ->deleteJson("/api/v1/tickets/{$ticket->id}");

    $response->assertStatus(200);
    
    $this->assertSoftDeleted('tickets', ['id' => $ticket->id]);
});

test('admin can delete ticket in any status', function () {
    $admin = User::factory()->admin()->create();
    $customer = User::factory()->customer()->create();
    
    $statuses = ['open', 'in_progress', 'resolved', 'closed'];
    
    foreach ($statuses as $status) {
        $ticket = Ticket::factory()->create([
            'user_id' => $customer->id,
            'status' => $status,
        ]);
        
        $token = $admin->createToken('Test', Abilities::getAbilities($admin))->plainTextToken;

        $response = $this->withToken($token)
            ->deleteJson("/api/v1/tickets/{$ticket->id}");

        $response->assertStatus(200);
        $this->assertSoftDeleted('tickets', ['id' => $ticket->id]);
    }
});
