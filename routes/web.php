<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ModerationController;
use App\Http\Controllers\Admin\AnalyticsController;
use App\Http\Middleware\AdminOnly;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

// Admin routes
Route::prefix('admin')->name('admin.')->middleware(['web', AdminOnly::class])->group(function () {
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Moderation
    Route::prefix('moderation')->name('moderation.')->group(function () {
        Route::get('/', [ModerationController::class, 'index'])->name('index');
        Route::get('/{id}', [ModerationController::class, 'show'])->name('show');
        Route::post('/{id}/review', [ModerationController::class, 'review'])->name('review');
        Route::post('/deactivate', [ModerationController::class, 'deactivate'])->name('deactivate');
        Route::post('/activate', [ModerationController::class, 'activate'])->name('activate');
    });

    // Analytics
    Route::prefix('analytics')->name('analytics.')->group(function () {
        Route::get('/activity', [AnalyticsController::class, 'activity'])->name('activity');
        Route::get('/user-distribution', [AnalyticsController::class, 'userDistribution'])->name('user-distribution');
        Route::get('/post-distribution', [AnalyticsController::class, 'postDistribution'])->name('post-distribution');
        Route::get('/engagement', [AnalyticsController::class, 'engagement'])->name('engagement');
    });
});
