<?php

use App\Models\User;
use App\Permissions\V1\Abilities;

// Priority 5: Validation Tests

test('unsupported filter returns 400', function () {
    $customer = User::factory()->customer()->create();
    $token = $customer->createToken('Test', Abilities::getAbilities($customer))->plainTextToken;

    $response = $this->withToken($token)
        ->getJson('/api/v1/tickets?filter[invalid_field]=value');

    $response->assertStatus(400)
        ->assertJson([
            'status' => 'error',
        ]);
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

test('unsupported include returns 400', function () {
    $customer = User::factory()->customer()->create();
    $token = $customer->createToken('Test', Abilities::getAbilities($customer))->plainTextToken;

    $response = $this->withToken($token)
        ->getJson('/api/v1/tickets?include=invalid_relation');

    $response->assertStatus(400)
        ->assertJson([
            'status' => 'error',
            'message' => 'Unsupported include parameter.',
        ]);
});

test('invalid status filter value returns 400', function () {
    $customer = User::factory()->customer()->create();
    $token = $customer->createToken('Test', Abilities::getAbilities($customer))->plainTextToken;

    $response = $this->withToken($token)
        ->getJson('/api/v1/tickets?filter[status]=invalid_status');

    $response->assertStatus(400);
});
