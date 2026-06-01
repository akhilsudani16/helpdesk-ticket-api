<?php

use App\Models\User;

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
        ]);
});

test('invalid login returns error', function () {
    $response = $this->postJson('/api/v1/auth/token', [
        'email' => 'invalid@example.com',
        'password' => 'wrongpassword',
        'device_name' => 'Test Device',
    ]);

    $response->assertStatus(422)
        ->assertJsonStructure([
            'message',
            'errors',
        ]);
});

test('user can revoke token', function () {
    $user = User::factory()->create();
    $token = $user->createToken('Test Device')->plainTextToken;

    $response = $this->withToken($token)
        ->deleteJson('/api/v1/auth/token');

    $response->assertStatus(200)
        ->assertJson([
            'status' => 'success',
            'message' => 'Token revoked successfully.',
        ]);
});
