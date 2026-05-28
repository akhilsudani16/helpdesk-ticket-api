<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreTicketCommentRequest;
use App\Http\Resources\V1\TicketCommentResource;
use App\Models\Ticket;
use App\Models\TicketComment;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

/**
 * @group Ticket Comments
 * 
 * APIs for managing ticket comments
 */
class TicketCommentController extends Controller
{
    /**
     * List ticket comments
     * 
     * Get all comments for a specific ticket.
     * Customers can only see public comments.
     * 
     * @authenticated
     * 
     * @urlParam ticket integer required The ticket ID. Example: 1
     */
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

    /**
     * Create ticket comment
     * 
     * Add a new comment to a ticket.
     * Customers can only create public comments.
     * 
     * @authenticated
     * 
     * @urlParam ticket integer required The ticket ID. Example: 1
     * @bodyParam body string required Comment text (3-2000 characters). Example: This issue has been resolved.
     * @bodyParam is_internal boolean Whether the comment is internal (admin/agent only). Example: false
     */
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
