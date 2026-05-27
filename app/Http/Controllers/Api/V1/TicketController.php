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
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;


class TicketController extends Controller
{
    use AuthorizesRequests;
    public function index(Request $request)
    {
        $this->authorize('viewAny', Ticket::class);

        $user = $request->user();

        // Start query
        $query = Ticket::query();

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

        // Load relationships based on include parameter
        $this->loadIncludes($query, $request);

        // Paginate results
        $perPage = $request->query('per_page', 15);
        $tickets = $query->paginate($perPage);

        return new TicketCollection($tickets);
    }

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

        return ApiResponse::success(
            new TicketResource($ticket),
            'Ticket created successfully.',
            201
        );
    }

    public function show(Request $request, Ticket $ticket)
    {
        $this->authorize('view', $ticket);

        // Load relationships based on include parameter
        $this->loadIncludesForModel($ticket, $request);

        return ApiResponse::success(new TicketResource($ticket));
    }


    public function patch(UpdateTicketRequest $request, Ticket $ticket)
    {
        $this->authorize('update', $ticket);

        $ticket->update($request->validated());

        return ApiResponse::success(
            new TicketResource($ticket->fresh()),
            'Ticket updated successfully.'
        );
    }

    public function update(ReplaceTicketRequest $request, Ticket $ticket)
    {
        $this->authorize('update', $ticket);

        $user = $request->user();

        if ($user->isCustomer()) {
            return ApiResponse::forbidden('Customers must use PATCH for partial updates.');
        }

        $ticket->update($request->validated());

        return ApiResponse::success(
            new TicketResource($ticket->fresh()),
            'Ticket replaced successfully.'
        );
    }

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
                $validIncludes[] = $include;
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
                $model->load($include);
            }
        }
    }
}
