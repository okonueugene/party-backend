<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Post;
use App\Models\Comment;
use App\Models\Flag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Get high-level dashboard metrics.
     */
    public function index()
    {
        $metrics = [
            'total_users' => User::count(),
            'active_users' => User::where('is_active', true)->count(),
            'verified_users' => User::whereNotNull('phone_verified_at')->count(),
            'total_posts' => Post::count(),
            'active_posts' => Post::where('is_active', true)->count(),
            'flagged_posts' => Post::where('is_flagged', true)->count(),
            'total_comments' => Comment::count(),
            'pending_flags' => Flag::where('status', 'pending')->count(),
            'recent_users' => User::orderBy('created_at', 'desc')->limit(10)->get(),
            'recent_posts' => Post::with('user')->orderBy('created_at', 'desc')->limit(10)->get(),
        ];

        return response()->json([
            'success' => true,
            'data' => $metrics,
        ]);
    }
}

