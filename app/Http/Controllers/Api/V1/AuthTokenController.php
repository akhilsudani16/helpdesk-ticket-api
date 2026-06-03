<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreAuthTokenRequest;
use App\Models\User;
use App\Permissions\V1\Abilities;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * @group Authentication
 * * APIs for managing authentication tokens
 */
class AuthTokenController extends Controller
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
                'email' => __('messages.auth.invalid_credentials'),
            ]);
        }

        $abilities = Abilities::getAbilities($user);

        $fullToken = $user->createToken($request->email, $abilities)->plainTextToken;

        $parts = explode('|', $fullToken, 2);
        $safeToken = $parts[1] ?? $fullToken;

        return ApiResponse::success([
            'token' => $safeToken,
            'abilities' => $abilities,
        ], __('messages.auth.token_created'));
    }

    /**
     * Revoke authentication token
     * * Delete the current user's API token.
     * * @authenticated
     */
    public function destroy(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return ApiResponse::success(null, __('messages.auth.token_deleted'));
    }

}
