<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreAuthTokenRequest;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthTokenController extends Controller
{
    public function store(StoreAuthTokenRequest $request)
    {
        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        // abilities for user role
        $abilities = $this->getAbilitiesForRole($user->role);

        // Create token with abilities
        $token = $user->createToken($request->device_name, $abilities)->plainTextToken;

        return ApiResponse::success([
            'token' => $token,
            'abilities' => $abilities,
        ], 'Token created successfully.');
    }


    public function destroy()
    {
        auth()->user()->currentAccessToken()->delete();

        return ApiResponse::success(null, 'Token revoked successfully.');
    }

    /**
     * Get abilities based on user role.
     */
    private function getAbilitiesForRole(string $role): array
    {
        return match ($role) {
            'admin' => [
                'tickets:view',
                'tickets:create',
                'tickets:update',
                'tickets:delete',
                'tickets:create-any',
                'tickets:update-any',
                'tickets:delete-any',
                'comments:view',
                'comments:create',
                'comments:create-internal',
                'users:view',
                'users:manage',
            ],
            'agent' => [
                'tickets:view',
                'tickets:update',
                'comments:view',
                'comments:create',
                'comments:create-internal',
                'users:view',
            ],
            'customer' => [
                'tickets:view',
                'tickets:create',
                'tickets:update',
                'tickets:delete',
                'comments:view',
                'comments:create',
            ],
            default => [],
        };
    }
}
