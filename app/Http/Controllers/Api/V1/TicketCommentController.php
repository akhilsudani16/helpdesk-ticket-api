<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreTicketCommentRequest;
use App\Http\Resources\V1\TicketCollection;
use App\Http\Resources\V1\TicketCommentResource;
use App\Models\Ticket;
use App\Models\TicketComment;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

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
    public function index(Request $request, Ticket $ticket,)
    {
        Gate::authorize('viewAny', [TicketComment::class, $ticket]);

        $query = $ticket->comments()->with('user');

        if ($request->user()->isCustomer()) {
            $query->where('is_internal', false);
        }

        return ApiResponse::success(
            new TicketCollection($query->paginate(5), 'comments'),
            __('messages.comments.retrieved')
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
     * @bodyParam body string required Comment text. Example: This issue has been resolved.
     * @bodyParam is_internal boolean Whether the comment is internal. Example: false
     */
     public function store(StoreTicketCommentRequest $request, Ticket $ticket)
     {
         Gate::authorize('create', [TicketComment::class, $ticket]);

         $data = $request->validated();

         // Determine is_internal based on user role
         if (Auth::user()->isAdmin() || Auth::user()->isAgent()) {
             $isInternal = true;
         } else {
             $isInternal = false;
         }

         if ($isInternal) {
             Gate::authorize('createInternal', TicketComment::class);
         }

         $comment = $ticket->comments()->create([
             'user_id' => $request->user()->id,
             'body' => $data['body'],
             'is_internal' => $isInternal,
         ]);


         $comment->load('user');

         return ApiResponse::success(new TicketCommentResource($comment), __('messages.comments.created'), 201);
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
        Gate::authorize('delete', $comment);

        if ($comment->ticket_id !== $ticket->id) {
            return ApiResponse::notFound(__('messages.errors.not_found'));
        }


        $comment->delete();

        return ApiResponse::success(null, __('messages.comments.deleted'));
    }
}
