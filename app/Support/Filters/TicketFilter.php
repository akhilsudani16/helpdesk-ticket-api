<?php

namespace App\Support\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class TicketFilter
{
    protected Builder $query;
    protected Request $request;
    protected array $allowedFilters = [
        'status',
        'priority',
        'customer_id',
        'assigned_to',
        'created_after',
    ];

    public function __construct(Builder $query, Request $request)
    {
        $this->query = $query;
        $this->request = $request;
    }

    /**
     * Apply filters to the query.
     */
    public function apply(): Builder
    {
        $filters = $this->request->query('filter', []);

        if (!is_array($filters)) {
            abort(400, 'Filter parameter must be an array.');
        }

        $unsupportedFilters = array_diff(array_keys($filters), $this->allowedFilters);
        if (!empty($unsupportedFilters)) {
            abort(400, 'Unsupported filter(s): ' . implode(', ', $unsupportedFilters) . '. Allowed filters: ' . implode(', ', $this->allowedFilters));
        }

        foreach ($filters as $filter => $value) {
            if (method_exists($this, $filter)) {
                $this->$filter($value);
            }
        }

        return $this->query;
    }

    /**
     * Filter by status.
     */
    protected function status(string $value): void
    {
        $allowedStatuses = ['open', 'in_progress', 'resolved', 'closed'];

        if (!in_array($value, $allowedStatuses)) {
            abort(400, "Invalid status value: {$value}. Allowed values: " . implode(', ', $allowedStatuses));
        }

        $this->query->where('status', $value);
    }

    /**
     * Filter by priority.
     */
    protected function priority(string $value): void
    {
        $allowedPriorities = ['low', 'medium', 'high', 'urgent'];

        if (!in_array($value, $allowedPriorities)) {
            abort(400, "Invalid priority value: {$value}. Allowed values: " . implode(', ', $allowedPriorities));
        }

        $this->query->where('priority', $value);
    }

    /**
     * Filter by customer ID.
     * Note: Authorization check should be done in the controller.
     */
    protected function customer_id(string $value): void
    {
        if (!is_numeric($value) || $value < 1) {
            abort(400, "Invalid customer_id: must be a positive integer.");
        }

        $this->query->where('user_id', $value);
    }

    /**
     * Filter by assigned agent.
     */
    protected function assigned_to(string $value): void
    {
        if (!is_numeric($value) || $value < 1) {
            abort(400, "Invalid assigned_to: must be a positive integer.");
        }

        $this->query->where('assigned_to', $value);
    }

    /**
     * Filter by created after date.
     */
    protected function created_after(string $value): void
    {
        $date = \DateTime::createFromFormat('Y-m-d', $value);

        if (!$date || $date->format('Y-m-d') !== $value) {
            abort(400, "Invalid date format for created_after. Expected format: YYYY-MM-DD (e.g., 2026-01-01)");
        }

        $this->query->where('created_at', '>=', $value . ' 00:00:00');
    }

    /**
     * Get allowed filters.
     */
    public function getAllowedFilters(): array
    {
        return $this->allowedFilters;
    }
}
