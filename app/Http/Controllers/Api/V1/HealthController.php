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
     *   "status": "ok",
     *   "version": "v1"
     * }
     */
    public function check()
    {
        return response()->json([
            'status' => 'ok',
            'version' => 'v1',
        ]);
    }
}
