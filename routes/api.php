<?php

use Illuminate\Support\Facades\Route;

// API Version 1 Routes
Route::prefix('v1')->group(function () {
    // Auth routes (public) with stricter rate limiting
    Route::middleware('throttle:auth')->group(function () {
        Route::post('/auth/token', [\App\Http\Controllers\Api\V1\AuthTokenController::class, 'store'])
            ->name('auth.token.store');
    });

    Route::middleware('auth:sanctum')->group(function () {
        Route::delete('/auth/token', [\App\Http\Controllers\Api\V1\AuthTokenController::class, 'destroy'])
            ->name('auth.token.destroy');
    });

    // Protected routes with normal API rate limiting
    Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {
        // Tickets
        Route::get('/tickets', [\App\Http\Controllers\Api\V1\TicketController::class, 'index'])
            ->name('tickets.index');
        
        Route::post('/tickets', [\App\Http\Controllers\Api\V1\TicketController::class, 'store'])
            ->name('tickets.store');
        
        Route::get('/tickets/{ticket}', [\App\Http\Controllers\Api\V1\TicketController::class, 'show'])
            ->name('tickets.show');
        
        Route::put('/tickets/{ticket}', [\App\Http\Controllers\Api\V1\TicketController::class, 'update'])
            ->name('tickets.update');
        
        Route::patch('/tickets/{ticket}', [\App\Http\Controllers\Api\V1\TicketController::class, 'patch'])
            ->name('tickets.patch');
        
        Route::delete('/tickets/{ticket}', [\App\Http\Controllers\Api\V1\TicketController::class, 'destroy'])
            ->name('tickets.destroy');

        // Ticket Comments
        Route::get('/tickets/{ticket}/comments', [\App\Http\Controllers\Api\V1\TicketCommentController::class, 'index'])
            ->name('ticket-comments.index');
        
        Route::post('/tickets/{ticket}/comments', [\App\Http\Controllers\Api\V1\TicketCommentController::class, 'store'])
            ->name('ticket-comments.store');

        Route::delete('/tickets/{ticket}/comments/{comment}', [\App\Http\Controllers\Api\V1\TicketCommentController::class, 'destroy'])
            ->name('ticket-comments.destroy');

        // Users
        Route::get('/users', [\App\Http\Controllers\Api\V1\UserController::class, 'index'])
            ->name('users.index');
        
        Route::get('/users/{user}', [\App\Http\Controllers\Api\V1\UserController::class, 'show'])
            ->name('users.show');
    });

    // Health check (public)
    Route::get('/health', [\App\Http\Controllers\Api\V1\HealthController::class, 'check'])
        ->name('health.check');
});

// Future API versions can be added here as separate prefix groups
// Route::prefix('v2')->group(base_path('routes/api_v2.php'));
