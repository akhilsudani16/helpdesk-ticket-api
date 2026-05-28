<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Cache\RateLimiting\Limit;

// Configure rate limiters
RateLimiter::for('auth', function (Request $request) {
    return Limit::perMinute(5)->by($request->ip());
});

RateLimiter::for('api', function (Request $request) {
    return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
});

// Auth routes (public) with stricter rate limiting
Route::middleware('throttle:auth')->group(function () {
    Route::post('/auth/token', [\App\Http\Controllers\Api\V1\AuthTokenController::class, 'store'])
        ->name('v1.auth.token.store');
});

Route::middleware('auth:sanctum')->group(function () {
    Route::delete('/auth/token', [\App\Http\Controllers\Api\V1\AuthTokenController::class, 'destroy'])
        ->name('v1.auth.token.destroy');
});

// Protected routes with normal API rate limiting
Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {
    // Tickets
    Route::get('/tickets', [\App\Http\Controllers\Api\V1\TicketController::class, 'index'])
        ->name('v1.tickets.index');
    
    Route::post('/tickets', [\App\Http\Controllers\Api\V1\TicketController::class, 'store'])
        ->name('v1.tickets.store');
    
    Route::get('/tickets/{ticket}', [\App\Http\Controllers\Api\V1\TicketController::class, 'show'])
        ->name('v1.tickets.show');
    
    Route::put('/tickets/{ticket}', [\App\Http\Controllers\Api\V1\TicketController::class, 'update'])
        ->name('v1.tickets.update');
    
    Route::patch('/tickets/{ticket}', [\App\Http\Controllers\Api\V1\TicketController::class, 'patch'])
        ->name('v1.tickets.patch');
    
    Route::delete('/tickets/{ticket}', [\App\Http\Controllers\Api\V1\TicketController::class, 'destroy'])
        ->name('v1.tickets.destroy');

    // Ticket Comments
    Route::get('/tickets/{ticket}/comments', [\App\Http\Controllers\Api\V1\TicketCommentController::class, 'index'])
        ->name('v1.ticket-comments.index');
    
    Route::post('/tickets/{ticket}/comments', [\App\Http\Controllers\Api\V1\TicketCommentController::class, 'store'])
        ->name('v1.ticket-comments.store');

    // Users
    Route::get('/users', [\App\Http\Controllers\Api\V1\UserController::class, 'index'])
        ->name('v1.users.index');
    
    Route::get('/users/{user}', [\App\Http\Controllers\Api\V1\UserController::class, 'show'])
        ->name('v1.users.show');
});

// Health check (public)
Route::get('/health', [\App\Http\Controllers\Api\V1\HealthController::class, 'check'])
    ->name('v1.health.check');
