<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Post;
use App\Models\Comment;
use App\Models\County;
use App\Models\Constituency;
use App\Models\Ward;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    /**
     * Get activity analytics.
     */
    public function activity(Request $request)
    {
        $days = $request->get('days', 30);

        $startDate = now()->subDays($days);

        // User registrations over time
        $userRegistrations = User::select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('COUNT(*) as count')
        )
            ->where('created_at', '>=', $startDate)
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Posts created over time
        $postCreations = Post::select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('COUNT(*) as count')
        )
            ->where('created_at', '>=', $startDate)
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Comments created over time
        $commentCreations = Comment::select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('COUNT(*) as count')
        )
            ->where('created_at', '>=', $startDate)
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'user_registrations' => $userRegistrations,
                'post_creations' => $postCreations,
                'comment_creations' => $commentCreations,
            ],
        ]);
    }

    /**
     * Get user distribution by geographic location.
     */
    public function userDistribution()
    {
        // Users by county
        $usersByCounty = County::withCount('users')
            ->orderBy('users_count', 'desc')
            ->get()
            ->map(function ($county) {
                return [
                    'county' => $county->name,
                    'count' => $county->users_count,
                ];
            });

        // Users by constituency
        $usersByConstituency = Constituency::withCount('users')
            ->orderBy('users_count', 'desc')
            ->limit(20)
            ->get()
            ->map(function ($constituency) {
                return [
                    'constituency' => $constituency->name,
                    'county' => $constituency->county->name,
                    'count' => $constituency->users_count,
                ];
            });

        // Users by ward
        $usersByWard = Ward::withCount('users')
            ->orderBy('users_count', 'desc')
            ->limit(20)
            ->get()
            ->map(function ($ward) {
                return [
                    'ward' => $ward->name,
                    'constituency' => $ward->constituency->name,
                    'county' => $ward->constituency->county->name,
                    'count' => $ward->users_count,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => [
                'by_county' => $usersByCounty,
                'by_constituency' => $usersByConstituency,
                'by_ward' => $usersByWard,
            ],
        ]);
    }

    /**
     * Get post distribution by geographic location.
     */
    public function postDistribution()
    {
        // Posts by county
        $postsByCounty = County::withCount('posts')
            ->orderBy('posts_count', 'desc')
            ->get()
            ->map(function ($county) {
                return [
                    'county' => $county->name,
                    'count' => $county->posts_count,
                ];
            });

        // Posts by constituency
        $postsByConstituency = Constituency::withCount('posts')
            ->orderBy('posts_count', 'desc')
            ->limit(20)
            ->get()
            ->map(function ($constituency) {
                return [
                    'constituency' => $constituency->name,
                    'county' => $constituency->county->name,
                    'count' => $constituency->posts_count,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => [
                'by_county' => $postsByCounty,
                'by_constituency' => $postsByConstituency,
            ],
        ]);
    }

    /**
     * Get engagement metrics.
     */
    public function engagement()
    {
        $metrics = [
            'total_likes' => DB::table('likes')->count(),
            'total_shares' => DB::table('shares')->count(),
            'total_comments' => Comment::count(),
            'average_likes_per_post' => Post::where('likes_count', '>', 0)->avg('likes_count'),
            'average_comments_per_post' => Post::where('comments_count', '>', 0)->avg('comments_count'),
            'most_liked_posts' => Post::orderBy('likes_count', 'desc')
                ->limit(10)
                ->with('user')
                ->get(),
            'most_commented_posts' => Post::orderBy('comments_count', 'desc')
                ->limit(10)
                ->with('user')
                ->get(),
        ];

        return response()->json([
            'success' => true,
            'data' => $metrics,
        ]);
    }
}

