<?php

namespace App\Support\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class SortValidator
{
    protected Request $request;
    protected array $allowedSorts;

    public function __construct(Request $request, array $allowedSorts = [])
    {
        $this->request = $request;
        $this->allowedSorts = $allowedSorts ?: ['created_at', 'updated_at', 'priority', 'status'];
    }

    /**
     * Apply sorting to the query.
     * 
     * @throws ValidationException
     */
    public function apply(Builder $query): void
    {
        $sortParam = $this->request->query('sort');

        if (!$sortParam) {
            // Default sorting
            $query->orderBy('created_at', 'desc');
            return;
        }

        $this->applySortFields($query, $sortParam);
    }

    /**
     * Apply multiple sort fields.
     */
    protected function applySortFields(Builder $query, string $sortParam): void
    {
        // Support multiple sort fields separated by comma
        foreach (explode(',', $sortParam) as $sort) {
            $sort = trim($sort);
            
            if (empty($sort)) {
                continue;
            }

            $this->applySingleSort($query, $sort);
        }
    }

    /**
     * Apply a single sort field.
     * 
     * @throws ValidationException
     */
    protected function applySingleSort(Builder $query, string $sort): void
    {
        $direction = 'asc';
        $field = $sort;

        // Check for descending order (prefix with -)
        if (str_starts_with($sort, '-')) {
            $direction = 'desc';
            $field = substr($sort, 1);
        }

        $this->validateSortField($field);
        $query->orderBy($field, $direction);
    }

    /**
     * Validate sort field is allowed.
     * 
     * @throws ValidationException
     */
    protected function validateSortField(string $field): void
    {
        if (!in_array($field, $this->allowedSorts, true)) {
            throw ValidationException::withMessages([
                'sort' => [
                    "Invalid sort field: {$field}. Allowed: " . implode(', ', $this->allowedSorts)
                ]
            ]);
        }
    }
}
