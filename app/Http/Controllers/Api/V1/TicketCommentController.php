<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
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
        if (!$this->isAble('comments.viewAny', $ticket)) {
            return $this->notAuthorized('You cannot view comments');
        }

        $query = $ticket->comments()->with('user');

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
        if (!$this->isAble('comments.create', $ticket)) {
            return $this->notAuthorized('You cannot create comments');
        }

        $data = $request->validated();
        $isInternal = $data['is_internal'] ?? false;

        if ($isInternal && !$this->isAble('comments.createInternal')) {
            return $this->notAuthorized('You cannot create internal comments.');
        }

        $comment = $ticket->comments()->create([
            'user_id' => $this->request->user()->id,
            'body' => $data['body'],
            'is_internal' => $isInternal,
        ]);

        $comment->load('user');

        return $this->ok(new TicketCommentResource($comment), 'Comment created successfully.', 201);
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

        if (!$this->isAble('comments.delete', $comment)) {
            return $this->notAuthorized('You cannot delete this comment');
        }

        $comment->delete();

        return $this->ok(null, 'Comment deleted successfully.');
    }
}
