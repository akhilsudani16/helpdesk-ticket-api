<?php

namespace App\Support\Filters;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class TicketFilter
{
    protected Request $request;
    protected User $user;
    protected array $allowedFilters;
    protected IncludeValidator $includeValidator;
    protected SortValidator $sortValidator;

    public function __construct(Request $request)
    {
        $this->request = $request;
        $this->user = Auth::user();
        $this->setAllowedFilters();
        
        // Initialize validators
        $this->includeValidator = new IncludeValidator($request);
        $this->sortValidator = new SortValidator($request);
    }

    /**
     * Set allowed filters based on user role.
     */
    protected function setAllowedFilters(): void
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
            // Customer
            $this->allowedFilters = [
                'status',
                'priority',
                'created_after',
            ];
        }
    }

    /**
     * Apply filters, includes, and sorting to the query.
     * 
     * @throws ValidationException
     */
    public function apply(Builder $query): Builder
    {
        // Apply user-specific scoping
        $this->applyUserScoping($query);

        // Apply includes (eager loading)
        $this->includeValidator->apply($query);

        // Apply filters
        $this->applyFilters($query);

        // Apply sorting
        $this->sortValidator->apply($query);

        return $query;
    }

    /**
     * Apply user role-based query scoping.
     */
    protected function applyUserScoping(Builder $query): void
    {
        if ($this->user->isAgent()) {
            $query->where('assigned_to', $this->user->id);
        } elseif ($this->user->isCustomer()) {
            $query->where('user_id', $this->user->id);
        }
        // Admin has no restrictions
    }

    /**
     * Apply filter conditions.
     * 
     * @throws ValidationException
     */
    protected function applyFilters(Builder $query): void
    {
        $filters = $this->request->query('filter', []);

        if (!is_array($filters) || empty($filters)) {
            return;
        }

        $this->validateFilters($filters);

        // Filter by status
        if (!empty($filters['status'])) {
            $this->validateEnum($filters['status'], TicketStatus::values(), 'status');
            $query->where('status', $filters['status']);
        }

        // Filter by priority
        if (!empty($filters['priority'])) {
            $this->validateEnum($filters['priority'], TicketPriority::values(), 'priority');
            $query->where('priority', $filters['priority']);
        }

        // Filter by customer
        if (isset($filters['customer_id']) || isset($filters['customer'])) {
            $customerId = $filters['customer_id'] ?? $filters['customer'];
            if (!empty($customerId)) {
                $this->validateNumeric($customerId, 'customer_id');
                $query->where('user_id', $customerId);
            }
        }

        // Filter by assigned agent
        if (isset($filters['assigned_to']) || isset($filters['assigned_agent'])) {
            $agentId = $filters['assigned_to'] ?? $filters['assigned_agent'];
            if (!empty($agentId)) {
                $this->validateNumeric($agentId, 'assigned_to');
                $query->where('assigned_to', $agentId);
            }
        }

        // Filter by created after
        if (!empty($filters['created_after'])) {
            $this->validateDate($filters['created_after']);
            $query->where('created_at', '>=', $filters['created_after'] . ' 00:00:00');
        }
    }

    /**
     * Validate filters are allowed for current user.
     * 
     * @throws ValidationException
     */
    protected function validateFilters(array $filters): void
    {
        $unsupportedFilters = array_diff(array_keys($filters), $this->allowedFilters);

        if (!empty($unsupportedFilters)) {
            throw ValidationException::withMessages([
                'filter' => ['Unsupported filter(s): ' . implode(', ', $unsupportedFilters)]
            ]);
        }
    }

    /**
     * Validate enum values.
     * 
     * @throws ValidationException
     */
    protected function validateEnum(string $value, array $allowed, string $field): void
    {
        if (!in_array($value, $allowed, true)) {
            throw ValidationException::withMessages([
                "filter.{$field}" => ["Invalid {$field}: {$value}. Allowed: " . implode(', ', $allowed)]
            ]);
        }
    }

    /**
     * Validate numeric values.
     * 
     * @throws ValidationException
     */
    protected function validateNumeric(string $value, string $field): void
    {
        if (!ctype_digit($value) || (int) $value < 1) {
            throw ValidationException::withMessages([
                "filter.{$field}" => ["The {$field} must be a valid positive number"]
            ]);
        }
    }

    /**
     * Validate date values.
     * 
     * @throws ValidationException
     */
    protected function validateDate(string $value): void
    {
        $date = \DateTime::createFromFormat('Y-m-d', $value);

        if (!$date || $date->format('Y-m-d') !== $value) {
            throw ValidationException::withMessages([
                'filter.created_after' => ['Invalid date format. Use YYYY-MM-DD']
            ]);
        }
    }
}
