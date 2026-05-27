<?php

use App\Http\Controllers\Api\V1\TicketCommentController;
use App\Http\Controllers\Api\V1\TicketController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\AuthTokenController;

Route::post('auth/token', [AuthTokenController::class, 'store']);
Route::post('auth/logout', [AuthTokenController::class, 'destroy'])->middleware('auth:sanctum');

Route::get('/tickets', [TicketController::class, 'index'])->middleware('auth:sanctum');
Route::post('/tickets', [TicketController::class, 'store'])->middleware('auth:sanctum');
Route::get('/tickets/{ticket}', [TicketController::class, 'show'])->middleware('auth:sanctum');
Route::put('/tickets/{ticket}', [TicketController::class, 'update'])->middleware('auth:sanctum');
Route::patch('tickets/{ticket}', [TicketController::class, 'patch'])->middleware('auth:sanctum');
Route::delete('/tickets/{ticket}', [TicketController::class, 'destroy'])->middleware('auth:sanctum');

Route::get('/tickets/{ticket}/comments', [TicketCommentController::class, 'index'])->middleware('auth:sanctum');
Route::post('/tickets/{ticket}/comments', [TicketCommentController::class, 'store'])->middleware('auth:sanctum');

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

