<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
| These are for serving the React app only
*/

// Serve the main React app
Route::get('/', function () {
    return view('welcome'); // Or your React app entry point
});

// Optional: Catch-all route for React Router (SPA routing)
Route::get('/{any}', function () {
    return view('welcome');
})->where('any', '.*');