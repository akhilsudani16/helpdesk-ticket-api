<?php

use Illuminate\Support\Facades\Route;


// API Version 1 Routes
Route::prefix('v1')->group(base_path('routes/api_v1.php'));

// Future API versions can be added here
// Route::prefix('v2')->group(base_path('routes/api_v2.php'));
