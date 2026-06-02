<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Api\V1\StoreTicketCommentRequest;
use App\Http\Resources\V1\TicketCommentResource;
use App\Models\Ticket;
use App\Models\TicketComment;
use App\Permissions\V1\Abilities;
use App\Support\ApiResponse;
use Illuminate\Support\Facades\Gate;

/**
 * @group Ticket Comments
 *
 * APIs for managing ticket comments
 */
class TicketCommentController extends ApiController
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
    public function index(Ticket $ticket)
    {
        Gate::authorize('view', $ticket);

        $query = $ticket->comments()->with('user');

        // Filter internal comments for customers
        if ($this->request->user()->isCustomer()) {
            $query->where('is_internal', false);
        }

        return $this->ok(TicketCommentResource::collection($query->get()));
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
     * @bodyParam body string required Comment text. Example: This issue has been resolved.
     * @bodyParam is_internal boolean Whether the comment is internal. Example: false
     */
    public function store(StoreTicketCommentRequest $request, Ticket $ticket)
    {
        Gate::authorize('view', $ticket);

        $data = $request->validated();

        // Check if user is trying to create internal comment
        $isInternal = $data['is_internal'] ?? false;

        if ($isInternal) {
            // Verify user has ability to create internal comments
            if (!$this->request->user()->tokenCan(Abilities::CreateInternalComment)) {
                return $this->notAuthorized( __('validation.internal_comment_permission'));
            }
        }

        $comment = $ticket->comments()->create([
            'user_id' => $this->request->user()->id,
            'body' => $data['body'],
            'is_internal' => $isInternal,
        ]);

        $comment->load('user');

        return $this->ok(new TicketCommentResource($comment), __('validation.comment_create'), 201);
    }

    /**
     * Delete ticket comment
     *
     * Delete a comment from a ticket.
     *
     * @authenticated
     *
     * @urlParam ticket integer required The ticket ID. Example: 1
     * @urlParam comment integer required The comment ID. Example: 1
     */
    public function destroy(Ticket $ticket, TicketComment $comment)
    {

        if ($comment->ticket_id !== $ticket->id) {
            return ApiResponse::notFound('Comment does not belong to this ticket');
        }

        Gate::authorize('delete', $comment);

        $comment->delete();

        return ApiResponse::success(null, __('validation.comment_deleted'));
    }
}
