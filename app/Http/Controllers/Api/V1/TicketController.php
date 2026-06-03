<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ReplaceTicketRequest;
use App\Http\Requests\Api\V1\StoreTicketRequest;
use App\Http\Requests\Api\V1\UpdateTicketRequest;
use App\Http\Resources\V1\TicketCollection;
use App\Http\Resources\V1\TicketResource;
use App\Models\Ticket;
use App\Support\ApiResponse;
use App\Support\Filters\TicketFilter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * @group Tickets
 *
 * APIs for managing support tickets
 */
class TicketController extends Controller
{
    /**
     * List tickets
     *
     * Get a paginated list of tickets with optional filtering and sorting.
     * Filtering, sorting, and include validation handled by TicketFilter.
     *
     * @authenticated
     */
    public function index(Request $request)
    {
        Gate::authorize('viewAny', Ticket::class);

        return ApiResponse::success(
            new TicketCollection(
                Ticket::filter(new TicketFilter($request))->paginate()
            ),
            __('messages.tickets.retrieved')
        );
    }

    /**
     * Create ticket
     *
     * Create a new support ticket. Validation rules handled by StoreTicketRequest.
     *
     * @authenticated
     */
    public function store(StoreTicketRequest $request)
    {
        Gate::authorize('create', Ticket::class);

        $ticket = Ticket::create($request->validated());

        return ApiResponse::success(
            (new TicketResource($ticket))->toArray($request),
            __('messages.tickets.created'),
            201
        );
    }

    /**
     * Show ticket
     *
     * Get details of a specific ticket.
     *
     * @authenticated
     */
    public function show(Ticket $ticket)
    {
        Gate::authorize('view', $ticket);

        return ApiResponse::success(
            (new TicketResource($ticket))->toArray(request()),
            __('messages.tickets.show')
        );
    }

    /**
     * Update ticket (PATCH)
     *
     * Partially update a ticket. Validation rules handled by UpdateTicketRequest.
     *
     * @authenticated
     */
    public function update(UpdateTicketRequest $request, Ticket $ticket)
    {
        Gate::authorize('update', $ticket);

        $ticket->update($request->validated());

        return ApiResponse::success(
            (new TicketResource($ticket))->toArray($request),
            __('messages.tickets.updated')
        );
    }

    /**
     * Replace ticket (PUT)
     *
     * Fully replace a ticket. Validation rules handled by ReplaceTicketRequest.
     * Customers cannot use PUT - they must use PATCH.
     *
     * @authenticated
     */
    public function replace(ReplaceTicketRequest $request, Ticket $ticket)
    {
        Gate::authorize('update', $ticket);

        if ($request->user()->isCustomer()) {
            return ApiResponse::forbidden(__('messages.errors.customers_use_patch'));
        }

        $ticket->update($request->validated());

        return ApiResponse::success(
            (new TicketResource($ticket))->toArray($request),
            __('messages.tickets.replaced')
        );
    }

    /**
     * Delete ticket
     *
     * Delete a ticket. Authorization rules handled by TicketPolicy.
     *
     * @authenticated
     */
    public function destroy(Request $request, Ticket $ticket)
    {

        Gate::authorize('delete', $ticket);

        // Check if customer is trying to delete a non-open ticket
        if ($request->user()->isCustomer() && $ticket->user_id !== $request->user()->id && $ticket->status->value !== 'open') {
            return ApiResponse::forbidden(__('messages.errors.cannot_delete_ticket'));
        }

        $ticket->delete();

        return ApiResponse::success(null, __('messages.tickets.deleted'));
    }
}
