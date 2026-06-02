<?php

use App\Models\User;
use App\Permissions\V1\Abilities;

// Priority 5: Authentication Tests

test('user can generate token with valid credentials', function () {
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => bcrypt('password'),
    ]);

    $response = $this->postJson('/api/v1/auth/token', [
        'email' => 'test@example.com',
        'password' => 'password',
        'device_name' => 'Postman',
    ]);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'status',
            'message',
            'data' => [
                'token',
                'abilities',
            ],
        ])
        ->assertJson([
            'status' => 'success',
            'message' => 'Token created successfully.',
        ]);

    // Verify token format - should be a clean token without ID prefix
    $token = $response->json('data.token');
    expect($token)->toBeString();
    expect(strlen($token))->toBeGreaterThan(40); // Sanctum tokens are long
    // Token should NOT contain | because we strip the prefix in controller
    expect($token)->not->toContain('|');
});

test('invalid login returns json error', function () {
    $response = $this->postJson('/api/v1/auth/token', [
        'email' => 'invalid@example.com',
        'password' => 'wrongpassword',
        'device_name' => 'Postman', // Use valid device name
    ]);

    $response->assertStatus(422)
        ->assertJsonStructure([
            'message',
            'errors',
        ])
        ->assertJsonValidationErrors(['email']);
});

test('missing device name returns validation error', function () {
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => bcrypt('password'),
    ]);

    $response = $this->postJson('/api/v1/auth/token', [
        'email' => 'test@example.com',
        'password' => 'password',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['device_name']);
});

test('user can revoke token', function () {
    $user = User::factory()->create();
    $token = $user->createToken('Test Device', Abilities::getAbilities($user))->plainTextToken;

    $response = $this->withToken($token)
        ->deleteJson('/api/v1/auth/token');

    $response->assertStatus(200)
        ->assertJson([
            'status' => 'success',
            'message' => 'Token revoked successfully.',
        ]);

    // After revoking, the token should no longer be in the database
    // We can't test with the same token because it's been deleted
    expect($user->tokens()->count())->toBe(0);
});
