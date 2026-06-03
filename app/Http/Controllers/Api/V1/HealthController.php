<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Support\ApiResponse;

/**
 * @group Health Check
 * 
 * API health check endpoint
 */
class HealthController extends Controller
{
    /**
     * Health check
     * 
     * Check if the API is running.
     * 
     * @response 200 {
     *   "status": "success",
     *   "message": "API is running",
     *   "data": {"version": "v1"},
     *   "errors": null
     * }
     */
    public function check()
    {
        return response()->json([
            'status' => 'success',
            'message' => __('messages.health.ok'),
            'data' => [
                'version' => 'v1',
            ],
            'errors' => null,
        ]);
    }
}
