<?php

use App\Http\Controllers\Api\V1\AuthTokenController;
use App\Http\Controllers\Api\V1\HealthController;
use App\Http\Controllers\Api\V1\TicketCommentController;
use App\Http\Controllers\Api\V1\TicketController;
use App\Http\Controllers\Api\V1\UserController;
use Illuminate\Support\Facades\Route;

// API Version 1 Routes

Route::prefix('v1')->group(function () {
    Route::middleware('throttle:auth')->group(function () {
        Route::post('/auth/token', [AuthTokenController::class, 'store'])->name('auth.token.store');
    });

    Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {

        Route::delete('/auth/token', [AuthTokenController::class, 'destroy'])->name('auth.token.destroy');

        // Tickets
        Route::get('/tickets', [TicketController::class, 'index'])->name('tickets.index');
        Route::post('/tickets', [TicketController::class, 'store'])->name('tickets.store');
        Route::get('/tickets/{ticket}', [TicketController::class, 'show'])->name('tickets.show');
        Route::put('/tickets/{ticket}', [TicketController::class, 'update'])->name('tickets.update');
        Route::patch('/tickets/{ticket}', [TicketController::class, 'patch'])->name('tickets.patch');
        Route::delete('/tickets/{ticket}', [TicketController::class, 'destroy'])->name('tickets.destroy');

        // Ticket Comments
        Route::get('/tickets/{ticket}/comments', [TicketCommentController::class, 'index'])->name('ticket-comments.index');
        Route::post('/tickets/{ticket}/comments', [TicketCommentController::class, 'store'])->name('ticket-comments.store');
        Route::delete('/tickets/{ticket}/comments/{comment}', [TicketCommentController::class, 'destroy'])->name('ticket-comments.destroy');

        // Users
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::get('/users/{user}', [UserController::class, 'show'])->name('users.show');
    });

    // Health check (public)
    Route::get('/health', [HealthController::class, 'check'])
        ->name('health.check');
});

// Future API versions can be added here as separate prefix groups
// Route::prefix('v2')->group(base_path('routes/api_v2.php'));
