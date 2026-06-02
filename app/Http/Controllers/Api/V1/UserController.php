<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Http\Resources\V1\UserResource;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

/**
 * @group Users
 * 
 * APIs for managing users (admin/agent only)
 */
class UserController extends ApiController
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
        Gate::authorize('viewAny', User::class);

        $users = User::paginate(15);

        return $this->ok(UserResource::collection($users));
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
        Gate::authorize('view', $user);

        return $this->ok(new UserResource($user));
    }
}
