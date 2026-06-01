<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Api\V1\StoreAuthTokenRequest;
use App\Models\User;
use App\Permissions\V1\Abilities;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * @group Authentication
 * * APIs for managing authentication tokens
 */
class AuthTokenController extends ApiController
{
    /**
     * Create authentication token
     * * Generate a new API token for the user.
     * * @bodyParam email string required The user's email address. Example: admin@example.com
     * @bodyParam password string required The user's password. Example: password
     * @bodyParam device_name string required The device name for the token. Example: Postman
     */
    public function store(StoreAuthTokenRequest $request)
    {
        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $abilities = Abilities::getAbilities($user);

        $fullToken = $user->createToken($request->email, $abilities)->plainTextToken;

        $parts = explode('|', $fullToken, 2);
        $safeToken = $parts[1] ?? $fullToken;

        return $this->ok([
            'token' => $safeToken,
            'abilities' => $abilities,
        ], 'Token created successfully.');
    }

    /**
     * Revoke authentication token
     * * Delete the current user's API token.
     * * @authenticated
     */
    public function destroy()
    {
        $this->request->user()->currentAccessToken()->delete();

        return $this->ok(null, 'Token revoked successfully.');
    }

}
