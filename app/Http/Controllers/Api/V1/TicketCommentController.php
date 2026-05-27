<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreTicketCommentRequest;
use App\Http\Resources\V1\TicketCommentResource;
use App\Models\Ticket;
use App\Models\TicketComment;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;


class TicketCommentController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request, Ticket $ticket)
    {
        $this->authorize('viewAny', [TicketComment::class, $ticket]);

        $user = $request->user();

        $query = $ticket->comments()->with('user');

        // Customers can only see public comments
        if ($user->isCustomer()) {
            $query->where('is_internal', false);
        }

        $comments = $query->get();

        return ApiResponse::success(
            TicketCommentResource::collection($comments)
        );
    }


    public function store(StoreTicketCommentRequest $request, Ticket $ticket)
    {
        $this->authorize('create', [TicketComment::class, $ticket]);

        $user = $request->user();
        $data = $request->validated();

        // Get is_internal value (will be null for customers due to validation)
        $isInternal = $data['is_internal'] ?? false;

        // Double-check authorization for internal comments
        if ($isInternal && !$user->can('createInternal', TicketComment::class)) {
            return ApiResponse::forbidden('You cannot create internal comments.');
        }

        $comment = $ticket->comments()->create([
            'user_id' => $user->id,
            'body' => $data['body'],
            'is_internal' => $isInternal,
        ]);

        $comment->load('user');

        return ApiResponse::success(
            new TicketCommentResource($comment),
            'Comment created successfully.',
            201
        );
    }
}
