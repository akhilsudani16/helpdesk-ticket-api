<?php

use App\Models\Ticket;
use App\Models\User;
use App\Permissions\V1\Abilities;

// Priority 5: Sorting Tests

test('sort descending created_at works', function () {
    $customer = User::factory()->customer()->create();
    $ticket1 = Ticket::factory()->create(['user_id' => $customer->id, 'created_at' => now()->subDays(2)]);
    $ticket2 = Ticket::factory()->create(['user_id' => $customer->id, 'created_at' => now()->subDay()]);
    $ticket3 = Ticket::factory()->create(['user_id' => $customer->id, 'created_at' => now()]);
    
    $token = $customer->createToken('Test', Abilities::getAbilities($customer))->plainTextToken;

    $response = $this->withToken($token)
        ->getJson('/api/v1/tickets?sort=-created_at');

    $response->assertStatus(200);
    
    $data = $response->json('data');
    expect($data[0]['id'])->toBe($ticket3->id);
    expect($data[1]['id'])->toBe($ticket2->id);
    expect($data[2]['id'])->toBe($ticket1->id);
});

test('sort ascending created_at works', function () {
    $customer = User::factory()->customer()->create();
    $ticket1 = Ticket::factory()->create(['user_id' => $customer->id, 'created_at' => now()->subDays(2)]);
    $ticket2 = Ticket::factory()->create(['user_id' => $customer->id, 'created_at' => now()->subDay()]);
    $ticket3 = Ticket::factory()->create(['user_id' => $customer->id, 'created_at' => now()]);
    
    $token = $customer->createToken('Test', Abilities::getAbilities($customer))->plainTextToken;

    $response = $this->withToken($token)
        ->getJson('/api/v1/tickets?sort=created_at');

    $response->assertStatus(200);
    
    $data = $response->json('data');
    expect($data[0]['id'])->toBe($ticket1->id);
});

test('unsupported sort returns 400', function () {
    $customer = User::factory()->customer()->create();
    $token = $customer->createToken('Test', Abilities::getAbilities($customer))->plainTextToken;

    $response = $this->withToken($token)
        ->getJson('/api/v1/tickets?sort=invalid_field');

    $response->assertStatus(400)
        ->assertJson([
            'status' => 'error',
        ]);
});
