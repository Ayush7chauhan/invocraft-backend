<?php

use Illuminate\Support\Facades\Route;

// API-only Laravel - web routes can be removed or used for health checks only
Route::get('/', function () {
    return response()->json([
        'message' => 'Laravel API Backend',
        'status' => 'running',
        'api_endpoint' => '/api'
    ]);
});
