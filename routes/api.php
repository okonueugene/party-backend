<?php

use Illuminate\Support\Facades\Route;

// Mobile App Controllers
use App\Http\Controllers\API\{
    AuthController,
    PostController,
    CommentController,
    EngagementController,
    GeographicController
};

// Admin Controllers
use App\Http\Controllers\Admin\{
    AuthController as AdminAuthController,
    DashboardController,
    ModerationController,
    AnalyticsController,
    UserController,
    PostController as AdminPostController
};

// Middleware
use App\Http\Middleware\{
    ApiAuthentication,
    RateLimiting,
    AdminOnly
};

/*
|--------------------------------------------------------------------------
| Mobile App API Routes - Public
|--------------------------------------------------------------------------
*/

// Mobile Authentication (Public)
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register'])
        ->middleware(RateLimiting::class . ':register');
    Route::post('/login', [AuthController::class, 'login'])
        ->middleware(RateLimiting::class . ':login');
    Route::post('/request-otp', [AuthController::class, 'requestOtp']);
    Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
    Route::post('/resend-otp', [AuthController::class, 'resendOtp']);
});

// Geographic Data (Public)
Route::prefix('geographic')->group(function () {
    // Get all counties
    Route::get('/counties', [GeographicController::class, 'counties']);
    
    // Get constituencies (all or by county)
    Route::get('/constituencies', [GeographicController::class, 'constituencies']);
    Route::get('/constituencies/{countyId}', [GeographicController::class, 'constituencies']);
    Route::get('/counties/{county}/constituencies', [GeographicController::class, 'constituencies']);
    
    // Get wards (all or by constituency)
    Route::get('/wards', [GeographicController::class, 'wards']);
    Route::get('/wards/{constituencyId}', [GeographicController::class, 'wards']);
    Route::get('/constituencies/{constituency}/wards', [GeographicController::class, 'wards']);
});

/*
|--------------------------------------------------------------------------
| Mobile App API Routes - Protected (Sanctum + ApiAuthentication)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth:sanctum', ApiAuthentication::class])->group(function () {
    
    // Mobile Auth (Protected)
    Route::prefix('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::put('/profile', [AuthController::class, 'updateProfile']);
        Route::get('/me', [AuthController::class, 'me']);
        Route::get('/refresh', [AuthController::class, 'refresh']);
    });
    
    // Posts
    Route::prefix('posts')->group(function () {
        Route::get('/', [PostController::class, 'index']);
        Route::post('/', [PostController::class, 'store'])
            ->middleware(RateLimiting::class . ':post');
        Route::get('/{id}', [PostController::class, 'show']);
        Route::get('/{post}', [PostController::class, 'show']); // Alternative with model binding
        Route::put('/{id}', [PostController::class, 'update']);
        Route::delete('/{id}', [PostController::class, 'destroy']);
        Route::delete('/{post}', [PostController::class, 'destroy']); // Alternative with model binding
        
        // Post-specific engagement
        Route::post('/{post}/like', [EngagementController::class, 'toggleLike']);
        Route::post('/{postId}/share', [EngagementController::class, 'share']);
        Route::post('/{post}/flag', [EngagementController::class, 'flagPost']);
        
        // Comments on specific post
        Route::get('/{post}/comments', [EngagementController::class, 'getComments']);
        Route::post('/{post}/comments', [EngagementController::class, 'addComment']);
        Route::get('/{postId}/comments', [CommentController::class, 'index']);
        Route::post('/{postId}/comments', [CommentController::class, 'store'])
            ->middleware(RateLimiting::class . ':comment');
    });
    
    // Comments (standalone)
    Route::prefix('comments')->group(function () {
        Route::put('/{id}', [CommentController::class, 'update']);
        Route::delete('/{id}', [CommentController::class, 'destroy']);
    });
    
    // Engagement (General)
    Route::prefix('engagement')->group(function () {
        Route::post('/like', [EngagementController::class, 'toggleLike']);
        Route::post('/flag', [EngagementController::class, 'flag']);
    });
});

/*
|--------------------------------------------------------------------------
| Admin API Routes - Public
|--------------------------------------------------------------------------
*/

Route::prefix('admin/auth')->group(function () {
    Route::post('/login', [AdminAuthController::class, 'login']);
});

/*
|--------------------------------------------------------------------------
| Admin API Routes - Protected (Sanctum + AdminOnly)
|--------------------------------------------------------------------------
*/

Route::prefix('admin')->middleware(['auth:sanctum', AdminOnly::class])->group(function () {
    
    // Admin Authentication
    Route::prefix('auth')->group(function () {
        Route::get('/me', [AdminAuthController::class, 'me']);
        Route::post('/logout', [AdminAuthController::class, 'logout']);
        Route::post('/logout-all', [AdminAuthController::class, 'logoutAll']);
        Route::post('/change-password', [AdminAuthController::class, 'changePassword']);
    });
    
    // Dashboard
    Route::prefix('dashboard')->group(function () {
        Route::get('/', [DashboardController::class, 'index']);
        Route::get('/users', [DashboardController::class, 'userStats']);
        Route::get('/posts', [DashboardController::class, 'postStats']);
        Route::get('/engagement', [DashboardController::class, 'engagementStats']);
    });
    
    // User Management
    Route::prefix('users')->group(function () {
        Route::get('/', [UserController::class, 'index']);
        Route::get('/{user}', [UserController::class, 'show']);
        Route::put('/{user}', [UserController::class, 'update']);
        Route::put('/{user}/suspend', [UserController::class, 'suspend']);
        Route::put('/{user}/activate', [UserController::class, 'activate']);
        Route::delete('/{user}', [UserController::class, 'destroy']);
    });
    
    // Post Management (Admin)
    Route::prefix('posts')->group(function () {
        Route::get('/', [AdminPostController::class, 'index']);
        Route::get('/{post}', [AdminPostController::class, 'show']);
        Route::delete('/{post}', [AdminPostController::class, 'destroy']);
        Route::post('/{post}/restore', [AdminPostController::class, 'restore']);
    });
    
    // Moderation
    Route::prefix('moderation')->group(function () {
        // Flags
        Route::get('/flags', [ModerationController::class, 'index']);
        Route::get('/flags/{flag}', [ModerationController::class, 'show']);
        Route::put('/flags/{flag}/review', [ModerationController::class, 'review']);
        Route::get('/{id}', [ModerationController::class, 'show']); // Legacy support
        Route::post('/{id}/review', [ModerationController::class, 'review']); // Legacy support
        
        // User Actions
        Route::post('/users/{user}/suspend', [ModerationController::class, 'suspendUser']);
        Route::post('/users/{user}/activate', [ModerationController::class, 'activateUser']);
        Route::post('/deactivate', [ModerationController::class, 'deactivate']); // Legacy support
        Route::post('/activate', [ModerationController::class, 'activate']); // Legacy support
        
        // Post Actions
        Route::delete('/posts/{post}', [ModerationController::class, 'deletePost']);
    });
    
    // Analytics
    Route::prefix('analytics')->group(function () {
        Route::get('/activity', [AnalyticsController::class, 'activity']);
        Route::get('/user-distribution', [AnalyticsController::class, 'userDistribution']);
        Route::get('/post-distribution', [AnalyticsController::class, 'postDistribution']);
        Route::get('/engagement', [AnalyticsController::class, 'engagement']);
        Route::get('/export', [AnalyticsController::class, 'export']);
    });
});
