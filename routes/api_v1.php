<?php

use App\Http\Controllers\Api\V1\AuthTokenController;
use App\Http\Controllers\Api\V1\HealthController;
use App\Http\Controllers\Api\V1\TicketCommentController;
use App\Http\Controllers\Api\V1\TicketController;
use App\Http\Controllers\Api\V1\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Cache\RateLimiting\Limit;

RateLimiter::for('auth', function (Request $request) {
    return Limit::perMinute(5)->by($request->ip());
});

RateLimiter::for('api', function (Request $request) {
    return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
});

Route::middleware('throttle:auth')->group(function () {
    Route::post('/auth/token', [AuthTokenController::class, 'store'])
        ->name('auth.token.store');
});

Route::middleware('auth:sanctum')->group(function () {
    Route::delete('/auth/token', [AuthTokenController::class, 'destroy'])->name('auth.token.destroy');
});

Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {

    Route::get('/tickets', [TicketController::class, 'index'])->name('tickets.index');
    Route::post('/tickets', [TicketController::class, 'store'])->name('tickets.store');
    Route::get('/tickets/{ticket}', [TicketController::class, 'show'])->name('tickets.show');
    Route::put('/tickets/{ticket}', [TicketController::class, 'update'])->name('tickets.update');
    Route::patch('/tickets/{ticket}', [TicketController::class, 'patch'])->name('tickets.patch');
    Route::delete('/tickets/{ticket}', [TicketController::class, 'destroy'])->name('tickets.destroy');

    Route::get('/tickets/{ticket}/comments', [TicketCommentController::class, 'index'])->name('ticket-comments.index');
    Route::post('/tickets/{ticket}/comments', [TicketCommentController::class, 'store'])->name('ticket-comments.store');

    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::get('/users/{user}', [UserController::class, 'show'])->name('users.show');
});

Route::get('/health', [HealthController::class, 'check'])->name('health.check');
