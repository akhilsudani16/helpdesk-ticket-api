<?php

namespace App\Support\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class IncludeValidator
{
    protected Request $request;
    protected array $allowedIncludes;

    public function __construct(Request $request, array $allowedIncludes = [])
    {
        $this->request = $request;
        $this->allowedIncludes = $allowedIncludes ?: ['customer', 'assignedAgent', 'comments'];
    }

    /**
     * Validate and apply includes to the query.
     */
    public function apply(Builder $query): void
    {
        $includeParam = $this->request->query('include', '');

        if (empty($includeParam)) {
            // Don't load any relationships by default
            return;
        }

        $requestedIncludes = $this->parseIncludes($includeParam);
        $this->validateIncludes($requestedIncludes);
        $relationships = $this->buildRelationships($requestedIncludes);

        if (!empty($relationships)) {
            $query->with($relationships);
        }
    }

    /**
     * Parse include parameter into array.
     */
    protected function parseIncludes(string $includeParam): array
    {
        return array_filter(array_map('trim', explode(',', $includeParam)));
    }

    /**
     * Validate requested includes.
     * 
     * @throws ValidationException
     */
    protected function validateIncludes(array $requestedIncludes): void
    {
        $unsupportedIncludes = array_diff($requestedIncludes, $this->allowedIncludes);

        if (!empty($unsupportedIncludes)) {
            throw ValidationException::withMessages([
                'include' => [
                    'Unsupported include parameter: ' . implode(', ', $unsupportedIncludes) . 
                    '. Allowed: ' . implode(', ', $this->allowedIncludes)
                ]
            ]);
        }
    }

    /**
     * Build relationships array from requested includes.
     */
    protected function buildRelationships(array $requestedIncludes): array
    {
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

        return $relationships;
    }
}
