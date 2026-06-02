<?php

namespace App\Support\Filters;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Exceptions\InvalidQueryParameterException;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TicketFilter
{
    protected Request $request;
    protected User $user;
    protected array $allowedFilters;

    public function __construct(Request $request)
    {
        $this->request = $request;
        $this->user = Auth::user();
        $this->setAllowedFilters();
    }

    /**
     * Set allowed filters based on a user role.
     */
    private function setAllowedFilters(): void
    {
        if ($this->user->isAdmin()) {
            $this->allowedFilters = [
                'status',
                'priority',
                'customer',
                'customer_id',
                'assigned_to',
                'assigned_agent',
                'created_after',
            ];
        } elseif ($this->user->isAgent()) {
            $this->allowedFilters = [
                'status',
                'priority',
                'assigned_to',
                'assigned_agent',
                'created_after',
            ];
        } else {
            $this->allowedFilters = [
                'status',
                'priority',
                'created_after',
            ];
        }
    }

    /**
     * Apply filters and sorting to the query.
     * @throws InvalidQueryParameterException
     */
    public function apply(Builder $query): Builder
    {
        // Apply role-based scoping
        $this->applyRoleScope($query);

        // Validate and apply includes
        $this->validateAndApplyIncludes($query);

        $filters = $this->request->query('filter', []);
        if (is_array($filters)) {
            $this->applyFilters($query, $filters);
        }

        $this->applySort($query);

        return $query;
    }

    /**
     * Apply role-based query scoping.
     */
    private function applyRoleScope(Builder $query): void
    {
        if ($this->user->isCustomer()) {
            // Customers can only see their own tickets
            $query->where('user_id', $this->user->id);
        } elseif ($this->user->isAgent()) {
            // Agents can only see tickets assigned to them
            $query->where('assigned_to', $this->user->id);
        }
        // Admins can see all tickets
    }

    /**
     * Validate and apply includes.
     */
    private function validateAndApplyIncludes(Builder $query): void
    {
        $includeParam = $this->request->query('include', '');

        if (empty($includeParam)) {
            // Default includes for list view
            $query->with(['customer', 'assignedAgent']);
            return;
        }

        $allowedIncludes = ['customer', 'assignedAgent', 'comments'];
        $requestedIncludes = array_filter(array_map('trim', explode(',', $includeParam)));

        // Check for unsupported includes
        $unsupportedIncludes = array_diff($requestedIncludes, $allowedIncludes);

        if (!empty($unsupportedIncludes)) {
            throw new InvalidQueryParameterException([
                'include' => __('validation.unsupported_include') . implode(', ', $unsupportedIncludes) . __('validation.allowed') . implode(', ', $allowedIncludes),
            ], __('validation.unsupported_include'));
        }

        // Build relationship array
        $relationships = [];

        if (in_array('customer', $requestedIncludes)) {
            $relationships[] = 'customer';
        }

        if (in_array('assignedAgent', $requestedIncludes)) {
            $relationships[] = 'assignedAgent';
        }

        if (in_array('comments', $requestedIncludes)) {
            $relationships[] = 'comments.user';
        }

        // Apply eager loading
        if (!empty($relationships)) {
            $query->with($relationships);
        }
    }

    /**
     * Apply filter conditions.
     */
    private function applyFilters(Builder $query, array $filters): void
    {
        $unsupportedFilters = array_diff(array_keys($filters), $this->allowedFilters);
        if (!empty($unsupportedFilters)) {
            throw new InvalidQueryParameterException([
                'filter' => __('validation.unsupported_filter') . implode(', ', $unsupportedFilters),
            ]);
        }

        foreach ($filters as $filter => $value) {
            if (method_exists($this, $filter)) {
                $this->$filter($query, $value);
            }
        }
    }

    /**
     * Apply sorting.
     */
    private function applySort(Builder $query): void
    {
        $sortParam = $this->request->query('sort');
        if (!$sortParam) {
            return;
        }

        $allowedSorts = ['created_at', 'updated_at', 'priority', 'status'];

        // Support multiple sort fields separated by a comma
        foreach (explode(',', $sortParam) as $sort) {
            $sort = trim($sort);
            if (empty($sort)) {
                continue;
            }

            $direction = 'asc';
            $field = $sort;

            if (str_starts_with($sort, '-')) {
                $direction = 'desc';
                $field = substr($sort, 1);
            }

            if (!in_array($field, $allowedSorts, true)) {
                throw new InvalidQueryParameterException([
                    'sort' => "Invalid sort field: {$field}. Allowed: " . implode(', ', $allowedSorts),
                ]);
            }

            $query->orderBy($field, $direction);
        }
    }

    /**
     * Filter by status.
     */
    protected function status(Builder $query, string $value): void
    {
        if (!in_array($value, TicketStatus::values(), true)) {
            throw new InvalidQueryParameterException([
                'filter.status' => "Invalid status: {$value}",
            ]);
        }

        $query->where('status', $value);
    }

    /**
     * Filter by priority.
     */
    protected function priority(Builder $query, string $value): void
    {
        if (!in_array($value, TicketPriority::values(), true)) {
            throw new InvalidQueryParameterException([
                'filter.priority' => "Invalid priority: {$value}",
            ]);
        }

        $query->where('priority', $value);
    }

    /**
     * Filter by customer ID.
     * @throws InvalidQueryParameterException
     */
    protected function customer_id(Builder $query, string $value): void
    {
        $this->validateNumericFilter($value, 'customer_id');
        $query->where('user_id', $value);
    }

    /**
     * Filter by customer ID alias.
     * @throws InvalidQueryParameterException
     */
    protected function customer(Builder $query, string $value): void
    {
        $this->customer_id($query, $value);
    }

    /**
     * Filter by assigned agent.
     * @throws InvalidQueryParameterException
     */
    protected function assigned_to(Builder $query, string $value): void
    {
        $this->validateNumericFilter($value, 'assigned_to');
        $query->where('assigned_to', $value);
    }

    /**
     * Filter by assigned agent alias.
     * @throws InvalidQueryParameterException
     */
    protected function assigned_agent(Builder $query, string $value): void
    {
        $this->assigned_to($query, $value);
    }

    /**
     * Filter by created after date.
     */
    protected function created_after(Builder $query, string $value): void
    {
        $date = \DateTime::createFromFormat('Y-m-d', $value);

        if (!$date || $date->format('Y-m-d') !== $value) {
            throw new InvalidQueryParameterException([
                'filter.created_after' => 'Invalid date format (YYYY-MM-DD)',
            ]);
        }

        $query->where('created_at', '>=', $value . ' 00:00:00');
    }

    /**
     * Validate numeric filter values.
     */
    private function validateNumericFilter(string $value, string $field): void
    {
        if (!ctype_digit($value) || (int) $value < 1) {
            throw new InvalidQueryParameterException([
                "filter.{$field}" => "Invalid {$field}",
            ]);
        }
    }
}
