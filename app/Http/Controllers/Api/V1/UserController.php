<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Http\Resources\V1\UserResource;
use App\Models\User;

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
        if (!$this->isAble('users.viewAny')) {
            return $this->notAuthorized('You cannot view users');
        }

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
        if (!$this->isAble('users.view', $user)) {
            return $this->notAuthorized('You cannot view this user');
        }

        return $this->ok(new UserResource($user));
    }
}
