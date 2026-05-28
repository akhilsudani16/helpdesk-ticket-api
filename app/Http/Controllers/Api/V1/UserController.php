<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\UserResource;
use App\Models\User;
use App\Support\ApiResponse;

/**
 * @group Users
 * 
 * APIs for managing users (admin/agent only)
 */
class UserController extends Controller
{
    /**
     * List users
     * 
     * Get a list of all users. Only accessible by admin and agents.
     * 
     * @authenticated
     */
    public function index()
    {
        $this->authorize('viewAny', User::class);

        $users = User::paginate(15);

        return ApiResponse::success(UserResource::collection($users));
    }

    /**
     * Show user
     * 
     * Get details of a specific user. Only accessible by admin and agents.
     * 
     * @authenticated
     * 
     * @urlParam user integer required The user ID. Example: 1
     */
    public function show(User $user)
    {
        $this->authorize('view', $user);

        return ApiResponse::success(new UserResource($user));
    }
}
