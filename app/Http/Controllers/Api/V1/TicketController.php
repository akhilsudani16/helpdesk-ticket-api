<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\InvalidQueryParameterException;
use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Api\V1\ReplaceTicketRequest;
use App\Http\Requests\Api\V1\StoreTicketRequest;
use App\Http\Requests\Api\V1\UpdateTicketRequest;
use App\Http\Resources\V1\TicketCollection;
use App\Http\Resources\V1\TicketResource;
use App\Models\Ticket;
use App\Policies\TicketPolicy;
use App\Support\Filters\TicketFilter;

/**
 * @group Tickets
 *
 * APIs for managing support tickets
 */
class TicketController extends ApiController
{
    protected string $policyClass = TicketPolicy::class;

    /**
     * List tickets
     *
     * Get a paginated list of tickets with optional filtering and sorting.
     *
     * @authenticated
     *
     * @queryParam include string Comma-separated list of relationships to include. Example: customer,comments
     * @queryParam filter[status] string Filter by status. Example: open
     * @queryParam filter[priority] string Filter by priority. Example: high
     * @queryParam filter[customer_id] integer Filter by customer ID. Example: 5
     * @queryParam filter[assigned_to] integer Filter by assigned agent ID. Example: 2
     * @queryParam filter[created_after] string Filter by creation date. Example: 2026-01-01
     * @queryParam sort string Sort by field. Prefix with - for descending. Example: -created_at
     * @queryParam page integer Page number. Example: 1
     * @queryParam per_page integer Items per page. Example: 15
     * @throws InvalidQueryParameterException
     */
    public function index(TicketFilter $filters)
    {
        // Build query
        $query = Ticket::filter($filters);

        $user = $this->request->user();
        if ($user->isAgent()) {
            $query->where('assigned_to', $user->id);
        }

        $tickets = $query->paginate($this->request->query('per_page', 15));

        return TicketCollection::make($tickets);
    }
    /**
     * Create ticket
     *
     * Create a new support ticket.
     *
     * @authenticated
     *
     * @bodyParam title string required Ticket title. Example: Payment failed
     * @bodyParam description string required Ticket description. Example: I paid but account not upgraded.
     * @bodyParam priority string required Priority level. Example: high
     * @bodyParam user_id integer User ID (admin only). Example: 5
     */
    public function store(StoreTicketRequest $request)
    {
        if (!$this->isAble('tickets.create')) {
            return $this->notAuthorized('You cannot create tickets');
        }

        $data = $request->validated();

        if (isset($data['user_id']) && !$this->isAble('tickets.createAny')) {
            return $this->notAuthorized('You cannot create tickets for other users');
        }

        if (!isset($data['user_id'])) {
            $data['user_id'] = $this->request->user()->id;
        }

        $ticket = Ticket::create($data)->load('customer', 'assignedAgent');

        return $this->ok(new TicketResource($ticket), 'Ticket created successfully.', 200);
    }

    /**
     * Show ticket
     *
     * Get details of a specific ticket.
     *
     * @authenticated
     *
     * @urlParam ticket integer required The ticket ID. Example: 1
     * @queryParam include string Comma-separated relationships. Example: customer,comments
     * @throws InvalidQueryParameterException
     */
    public function show(Ticket $ticket)
    {
        if (!$this->isAble('tickets.view', $ticket)) {
            return $this->notAuthorized('You cannot view this ticket');
        }

        // Validate includes
        $allowedIncludes = ['customer', 'assignedAgent', 'comments'];
        $requestedIncludes = $this->validateIncludes($allowedIncludes);

        // Always load customer and assignedAgent for single ticket view
        $defaultRelations = ['customer', 'assignedAgent'];

        // Add comments if requested
        if (in_array('comments', $requestedIncludes)) {
            $defaultRelations[] = 'comments.user';
        }

        // Eager load relationships
        $ticket->loadMissing($defaultRelations);

        return $this->ok(new TicketResource($ticket));
    }

    /**
     * Update ticket (PATCH)
     *
     * Partially update a ticket.
     *
     * @authenticated
     *
     * @urlParam ticket integer required The ticket ID. Example: 1
     * @bodyParam title string Ticket title. Example: Updated title
     * @bodyParam description string Ticket description. Example: Updated description
     * @bodyParam status string Status. Example: in_progress
     * @bodyParam priority string Priority. Example: medium
     * @bodyParam assigned_to integer Assigned agent ID. Example: 2
     */
    public function patch(UpdateTicketRequest $request, Ticket $ticket)
    {
        if (!$this->isAble('tickets.update', $ticket)) {
            return $this->notAuthorized('You cannot update this ticket');
        }

        $ticket->update($request->validated());
        $ticket->load(['customer', 'assignedAgent']);

        return $this->ok(new TicketResource($ticket), 'Ticket updated successfully.');
    }

    /**
     * Replace ticket (PUT)
     *
     * Fully replace a ticket. All fields are required.
     *
     * @authenticated
     *
     * @urlParam ticket integer required The ticket ID. Example: 1
     * @bodyParam title string required Ticket title. Example: Payment failed
     * @bodyParam description string required Ticket description. Example: Full description
     * @bodyParam status string required Status. Example: open
     * @bodyParam priority string required Priority level. Example: high
     * @bodyParam assigned_to integer Assigned agent ID. Example: 2
     */
    public function update(ReplaceTicketRequest $request, Ticket $ticket)
    {
        if (!$this->isAble('tickets.update', $ticket)) {
            return $this->notAuthorized('You cannot update this ticket');
        }

        if ($this->request->user()->isCustomer()) {
            return $this->notAuthorized('Customers must use PATCH for partial updates.');
        }

        $ticket->update($request->validated());
        $ticket->load(['customer', 'assignedAgent']);

        return $this->ok(new TicketResource($ticket), 'Ticket replaced successfully.');
    }

    /**
     * Delete ticket
     *
     * Delete a ticket.
     *
     * @authenticated
     *
     * @urlParam ticket integer required The ticket ID. Example: 1
     */
    public function destroy(Ticket $ticket)
    {
        if ($this->isAble('tickets.delete', $ticket)) {
            $ticket->delete();

            return $this->ok('Ticket successfully deleted');
        }

        return $this->notAuthorized('You cannot delete this ticket');
    }
}
