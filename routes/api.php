<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\PostController;
use App\Http\Controllers\API\CommentController;
use App\Http\Controllers\API\EngagementController;
use App\Http\Controllers\API\GeographicController;
use App\Http\Middleware\ApiAuthentication;
use App\Http\Middleware\RateLimiting;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Public routes
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register'])
        ->middleware(RateLimiting::class . ':register');
    Route::post('/login', [AuthController::class, 'login'])
        ->middleware(RateLimiting::class . ':login');
    Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
    Route::post('/resend-otp', [AuthController::class, 'resendOtp']);
});

// Geographic data (public)
Route::prefix('geographic')->group(function () {
    Route::get('/counties', [GeographicController::class, 'counties']);
    Route::get('/constituencies', [GeographicController::class, 'constituencies']);
    Route::get('/constituencies/{countyId}', [GeographicController::class, 'constituencies']);
    Route::get('/wards', [GeographicController::class, 'wards']);
    Route::get('/wards/{constituencyId}', [GeographicController::class, 'wards']);
});

// Protected routes (require authentication)
Route::middleware([ApiAuthentication::class])->group(function () {
    // Auth
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    // Posts
    Route::prefix('posts')->group(function () {
        Route::get('/', [PostController::class, 'index']);
        Route::post('/', [PostController::class, 'store'])
            ->middleware(RateLimiting::class . ':post');
        Route::get('/{id}', [PostController::class, 'show']);
        Route::put('/{id}', [PostController::class, 'update']);
        Route::delete('/{id}', [PostController::class, 'destroy']);
    });

    // Comments
    Route::prefix('posts/{postId}/comments')->group(function () {
        Route::get('/', [CommentController::class, 'index']);
        Route::post('/', [CommentController::class, 'store']);
    });

    Route::prefix('comments')->group(function () {
        Route::put('/{id}', [CommentController::class, 'update']);
        Route::delete('/{id}', [CommentController::class, 'destroy']);
    });

    // Engagement
    Route::prefix('engagement')->group(function () {
        Route::post('/like', [EngagementController::class, 'toggleLike']);
        Route::post('/posts/{postId}/share', [EngagementController::class, 'share']);
        Route::post('/flag', [EngagementController::class, 'flag']);
    });
});

