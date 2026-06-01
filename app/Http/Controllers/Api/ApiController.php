<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\InvalidQueryParameterException;
use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Base controller for API endpoints.
 * Provides common API response and authorization methods.
 */
abstract class ApiController extends Controller
{
    /**
     * Current request instance.
     */
    protected Request $request;

    /**
     * Bootstrap the controller.
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * Check if the current user has an ability (via Gate or Sanctum).
     */
    protected function isAble(string $ability, $resource = null): bool
    {
        if ($resource) {
            return Gate::allows($ability, $resource);
        }

        return Gate::allows($ability);
    }

    /**
     * Return a forbidden/unauthorized response.
     */
    protected function notAuthorized(string $message = 'Unauthorized')
    {
        return ApiResponse::forbidden($message);
    }

    /**
     * Return a success response.
     */
    protected function ok($data = null, string $message = 'Success', int $code = 200)
    {
        return ApiResponse::success($data, $message, $code);
    }

    /**
     * Check if a relationship should be included based on query parameter.
     */
    protected function include(string $relationship): bool
    {
        $includes = array_filter(explode(',', $this->request->query('include', '')));
        return in_array($relationship, $includes);
    }

    /**
     * Validate and get requested includes.
     *
     * @param array $allowedIncludes List of allowed include values
     * @return array Validated includes
     * @throws InvalidQueryParameterException
     */
    protected function validateIncludes(array $allowedIncludes): array
    {
        $includeParam = $this->request->query('include', '');

        if (empty($includeParam)) {
            return [];
        }

        $requestedIncludes = array_filter(array_map('trim', explode(',', $includeParam)));

        // Check for unsupported includes
        $unsupportedIncludes = array_diff($requestedIncludes, $allowedIncludes);

        if (!empty($unsupportedIncludes)) {
            throw new InvalidQueryParameterException([
                'include' => 'Unsupported include parameter: ' . implode(', ', $unsupportedIncludes) . '. Allowed: ' . implode(', ', $allowedIncludes),
            ], 'Unsupported include parameter.');
        }

        return $requestedIncludes;
    }
}
