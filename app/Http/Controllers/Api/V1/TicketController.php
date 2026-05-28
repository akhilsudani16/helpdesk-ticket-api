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
     * 
     * @authenticated
     * 
     * @queryParam include string Comma-separated list of relationships to include (customer,assignedAgent,comments). Example: customer,comments
     * @queryParam filter[status] string Filter by status. Example: open
     * @queryParam filter[priority] string Filter by priority. Example: high
     * @queryParam filter[customer_id] integer Filter by customer ID. Example: 5
     * @queryParam filter[assigned_to] integer Filter by assigned agent ID. Example: 2
     * @queryParam filter[created_after] string Filter by creation date. Example: 2026-01-01
     * @queryParam sort string Sort by field(s). Prefix with - for descending. Example: -created_at
     * @queryParam page integer Page number. Example: 1
     * @queryParam per_page integer Items per page. Example: 15
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Ticket::class);

        $user = $request->user();
        
        // Start query
        $query = Ticket::query();

        // Always load customer and assignedAgent for summaries
        $query->with(['customer', 'assignedAgent']);

        // Customers can only see their own tickets
        if ($user->isCustomer()) {
            $query->where('user_id', $user->id);
            
            // Prevent customers from filtering by other customer IDs
            if ($request->has('filter.customer_id') && $request->input('filter.customer_id') != $user->id) {
                return ApiResponse::forbidden('You cannot view other customers\' tickets.');
            }
        }

        // Apply filters
        $filter = new TicketFilter($query, $request);
        $query = $filter->apply();

        // Apply sorting
        $this->applySorting($query, $request);

        // Load additional relationships based on include parameter
        $this->loadIncludes($query, $request);

        // Paginate results
        $perPage = $request->query('per_page', 15);
        $tickets = $query->paginate($perPage);

        return new TicketCollection($tickets);
    }

    /**
     * Create ticket
     * 
     * Create a new support ticket.
     * 
     * @authenticated
     * 
     * @bodyParam title string required Ticket title (5-120 characters). Example: Payment failed
     * @bodyParam description string required Ticket description (min 20 characters). Example: I paid for the plan, but my account is not upgraded.
     * @bodyParam priority string required Priority level. Example: high
     * @bodyParam user_id integer User ID (admin only). Example: 5
     */
    public function store(StoreTicketRequest $request)
    {
        $this->authorize('create', Ticket::class);

        $user = $request->user();
        $data = $request->validated();

        // Handle user_id for ticket creation
        if (isset($data['user_id'])) {
            // Only admin with create-any ability can create tickets for other users
            if (!$user->can('createAny', Ticket::class)) {
                return ApiResponse::forbidden('You cannot create tickets for other users.');
            }
        } else {
            // Default to current user
            $data['user_id'] = $user->id;
        }

        $ticket = Ticket::create($data);

        // Load customer and assignedAgent for response
        $ticket->load(['customer', 'assignedAgent']);

        return ApiResponse::success(
            new TicketResource($ticket),
            'Ticket created successfully.',
            201
        );
    }

    /**
     * Show ticket
     * 
     * Get details of a specific ticket.
     * 
     * @authenticated
     * 
     * @urlParam ticket integer required The ticket ID. Example: 1
     * @queryParam include string Comma-separated list of relationships to include. Example: customer,comments
     */
    public function show(Request $request, Ticket $ticket)
    {
        $this->authorize('view', $ticket);

        // Always load customer and assignedAgent for summaries
        $ticket->load(['customer', 'assignedAgent']);

        // Load additional relationships based on include parameter
        $this->loadIncludesForModel($ticket, $request);

        return ApiResponse::success(new TicketResource($ticket));
    }

    /**
     * Update ticket (PATCH)
     * 
     * Partially update a ticket. Only provided fields will be updated.
     * 
     * @authenticated
     * 
     * @urlParam ticket integer required The ticket ID. Example: 1
     * @bodyParam title string Ticket title (5-120 characters). Example: Payment issue resolved
     * @bodyParam description string Ticket description (min 20 characters). Example: Updated description
     * @bodyParam status string Status (admin/agent only). Example: in_progress
     * @bodyParam priority string Priority (admin/agent only). Example: medium
     * @bodyParam assigned_to integer Assigned agent ID (admin/agent only). Example: 2
     */
    public function patch(UpdateTicketRequest $request, Ticket $ticket)
    {
        $this->authorize('update', $ticket);

        $ticket->update($request->validated());

        // Load customer and assignedAgent for response
        $ticket->load(['customer', 'assignedAgent']);

        return ApiResponse::success(
            new TicketResource($ticket->fresh()),
            'Ticket updated successfully.'
        );
    }

    /**
     * Replace ticket (PUT)
     * 
     * Fully replace a ticket. All fields are required.
     * 
     * @authenticated
     * 
     * @urlParam ticket integer required The ticket ID. Example: 1
     * @bodyParam title string required Ticket title (5-120 characters). Example: Payment failed
     * @bodyParam description string required Ticket description (min 20 characters). Example: Full description
     * @bodyParam status string required Status. Example: open
     * @bodyParam priority string required Priority level. Example: high
     * @bodyParam assigned_to integer Assigned agent ID. Example: 2
     */
    public function update(ReplaceTicketRequest $request, Ticket $ticket)
    {
        $this->authorize('update', $ticket);

        $user = $request->user();
        
        // Customers cannot use PUT to update tickets
        if ($user->isCustomer()) {
            return ApiResponse::forbidden('Customers must use PATCH for partial updates.');
        }

        $ticket->update($request->validated());

        // Load customer and assignedAgent for response
        $ticket->load(['customer', 'assignedAgent']);

        return ApiResponse::success(
            new TicketResource($ticket->fresh()),
            'Ticket replaced successfully.'
        );
    }

    /**
     * Delete ticket
     * 
     * Delete a ticket. Customers can only delete their own open tickets.
     * 
     * @authenticated
     * 
     * @urlParam ticket integer required The ticket ID. Example: 1
     */
    public function destroy(Ticket $ticket)
    {
        $this->authorize('delete', $ticket);

        $ticket->delete();

        return ApiResponse::success(null, 'Ticket deleted successfully.');
    }

    /**
     * Apply sorting to the query.
     */
    private function applySorting($query, Request $request): void
    {
        $sortParam = $request->query('sort');
        
        if (!$sortParam) {
            return;
        }

        $allowedSorts = ['created_at', 'updated_at', 'priority', 'status'];
        $sorts = explode(',', $sortParam);

        foreach ($sorts as $sort) {
            $direction = 'asc';
            $field = $sort;

            if (str_starts_with($sort, '-')) {
                $direction = 'desc';
                $field = substr($sort, 1);
            }

            if (!in_array($field, $allowedSorts)) {
                abort(400, "Unsupported sort field: {$field}. Allowed fields: " . implode(', ', $allowedSorts));
            }

            $query->orderBy($field, $direction);
        }
    }

    /**
     * Load relationships based on include parameter.
     */
    private function loadIncludes($query, Request $request): void
    {
        $includes = $request->query('include', '');
        $includesArray = array_filter(explode(',', $includes));
        
        $allowedIncludes = ['customer', 'assignedAgent', 'comments'];
        $validIncludes = [];

        foreach ($includesArray as $include) {
            if (in_array($include, $allowedIncludes)) {
                // Only load comments as additional include
                // customer and assignedAgent are already loaded for summaries
                if ($include === 'comments') {
                    $validIncludes[] = $include;
                }
            }
        }

        if (!empty($validIncludes)) {
            $query->with($validIncludes);
        }
    }

    /**
     * Load relationships for a single model.
     */
    private function loadIncludesForModel($model, Request $request): void
    {
        $includes = $request->query('include', '');
        $includesArray = array_filter(explode(',', $includes));
        
        $allowedIncludes = ['customer', 'assignedAgent', 'comments'];

        foreach ($includesArray as $include) {
            if (in_array($include, $allowedIncludes)) {
                // Only load comments as additional include
                // customer and assignedAgent are already loaded for summaries
                if ($include === 'comments') {
                    $model->load($include);
                }
            }
        }
    }
}
