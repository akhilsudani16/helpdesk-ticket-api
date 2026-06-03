<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\TicketCollection;
use App\Http\Resources\V1\UserResource;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Support\Facades\Gate;

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
        Gate::authorize('viewAny', User::class);

        return ApiResponse::success(
            new TicketCollection(User::paginate(15), 'users'),
            __('messages.users.retrieved')
        );
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
        if($user->isCustomer()){
            return ApiResponse::forbidden(__('messages.users.cannot_view_customer'));
        }
        Gate::authorize('view', $user);

        return ApiResponse::success(new UserResource($user), __('messages.users.show'));
    }
}
