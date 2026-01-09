<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{User, Post, Comment, Flag, Like};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Get dashboard overview metrics
     * GET /api/admin/dashboard
     */
    public function index(Request $request)
    {
        // Cache for 5 minutes
        $metrics = Cache::remember('admin.dashboard.metrics', 300, function () {
            return [
                'users' => $this->getUserMetrics(),
                'posts' => $this->getPostMetrics(),
                'engagement' => $this->getEngagementMetrics(),
                'moderation' => $this->getModerationMetrics(),
            ];
        });

        $recentActivity = [
            'recent_users' => $this->getRecentUsers(),
            'recent_posts' => $this->getRecentPosts(),
            'recent_flags' => $this->getRecentFlags(),
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'metrics' => $metrics,
                'recent_activity' => $recentActivity,
                'timestamp' => now()->toIso8601String(),
            ],
        ]);
    }

    /**
     * Get detailed user statistics
     * GET /api/admin/dashboard/users
     */
    public function userStats(Request $request)
    {
        $period = $request->input('period', '7days'); // 7days, 30days, 90days, year
        
        $data = [
            'overview' => $this->getUserMetrics(),
            'growth' => $this->getUserGrowthData($period),
            'by_county' => $this->getUsersByCounty(),
            'by_verification' => $this->getUsersByVerification(),
        ];

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Get detailed post statistics
     * GET /api/admin/dashboard/posts
     */
    public function postStats(Request $request)
    {
        $period = $request->input('period', '7days');
        
        $data = [
            'overview' => $this->getPostMetrics(),
            'growth' => $this->getPostGrowthData($period),
            'by_county' => $this->getPostsByCounty(),
            'by_type' => $this->getPostsByType(),
        ];

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Get engagement statistics
     * GET /api/admin/dashboard/engagement
     */
    public function engagementStats(Request $request)
    {
        $period = $request->input('period', '7days');
        
        $data = [
            'overview' => $this->getEngagementMetrics(),
            'trend' => $this->getEngagementTrend($period),
            'top_posts' => $this->getTopPosts(),
            'top_users' => $this->getTopUsers(),
        ];

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    // ============ PRIVATE HELPER METHODS ============

    private function getUserMetrics(): array
    {
        $now = now();
        $yesterday = $now->copy()->subDay();
        $lastWeek = $now->copy()->subWeek();

        $totalUsers = User::count();
        $todayUsers = User::whereDate('created_at', $now)->count();
        $yesterdayUsers = User::whereDate('created_at', $yesterday)->count();
        
        return [
            'total' => $totalUsers,
            'active' => User::active()->count(),
            'verified' => User::verified()->count(),
            'suspended' => User::where('is_suspended', true)->count(),
            'admins' => User::where('is_admin', true)->count(),
            'today' => $todayUsers,
            'yesterday' => $yesterdayUsers,
            'change_24h' => $yesterdayUsers > 0 
                ? round((($todayUsers - $yesterdayUsers) / $yesterdayUsers) * 100, 1)
                : 0,
            'this_week' => User::where('created_at', '>=', $lastWeek)->count(),
        ];
    }

    private function getPostMetrics(): array
    {
        $now = now();
        $yesterday = $now->copy()->subDay();
        $lastWeek = $now->copy()->subWeek();

        $totalPosts = Post::count();
        $todayPosts = Post::whereDate('created_at', $now)->count();
        $yesterdayPosts = Post::whereDate('created_at', $yesterday)->count();

        return [
            'total' => $totalPosts,
            'active' => Post::whereNull('deleted_at')->count(),
            'flagged' => Post::where('is_flagged', true)->count(),
            'today' => $todayPosts,
            'yesterday' => $yesterdayPosts,
            'change_24h' => $yesterdayPosts > 0 
                ? round((($todayPosts - $yesterdayPosts) / $yesterdayPosts) * 100, 1)
                : 0,
            'this_week' => Post::where('created_at', '>=', $lastWeek)->count(),
            'with_images' => Post::whereNotNull('image')->count(),
            'with_audio' => Post::whereNotNull('audio')->count(),
        ];
    }

    private function getEngagementMetrics(): array
    {
        $totalLikes = Like::count();
        $totalComments = Comment::count();
        $totalPosts = Post::count();

        return [
            'total_likes' => $totalLikes,
            'total_comments' => $totalComments,
            'avg_likes_per_post' => $totalPosts > 0 ? round($totalLikes / $totalPosts, 2) : 0,
            'avg_comments_per_post' => $totalPosts > 0 ? round($totalComments / $totalPosts, 2) : 0,
            'today_likes' => Like::whereDate('created_at', now())->count(),
            'today_comments' => Comment::whereDate('created_at', now())->count(),
        ];
    }

    private function getModerationMetrics(): array
    {
        return [
            'pending_flags' => Flag::where('status', 'pending')->count(),
            'reviewed_flags' => Flag::where('status', 'reviewed')->count(),
            'action_taken' => Flag::where('status', 'action_taken')->count(),
            'flagged_posts' => Post::where('is_flagged', true)->count(),
            'suspended_users' => User::where('is_suspended', true)->count(),
        ];
    }

    private function getRecentUsers(int $limit = 10): array
    {
        return User::with('ward.constituency.county')
                   ->latest()
                   ->limit($limit)
                   ->get()
                   ->map(function ($user) {
                       return [
                           'id' => $user->id,
                           'name' => $user->name,
                           'phone_number' => $user->phone_number,
                           'ward' => $user->ward?->name,
                           'constituency' => $user->ward?->constituency?->name,
                           'county' => $user->ward?->constituency?->county?->name,
                           'verified' => !is_null($user->phone_verified_at),
                           'created_at' => $user->created_at->toIso8601String(),
                       ];
                   })
                   ->toArray();
    }

    private function getRecentPosts(int $limit = 10): array
    {
        return Post::with(['user', 'ward.constituency.county'])
                   ->withCount(['likes', 'comments'])
                   ->latest()
                   ->limit($limit)
                   ->get()
                   ->map(function ($post) {
                       return [
                           'id' => $post->id,
                           'content' => $post->content ? substr($post->content, 0, 100) . '...' : null,
                           'user' => [
                               'id' => $post->user->id,
                               'name' => $post->user->name,
                           ],
                           'ward' => $post->ward?->name,
                           'likes_count' => $post->likes_count,
                           'comments_count' => $post->comments_count,
                           'is_flagged' => $post->is_flagged,
                           'created_at' => $post->created_at->toIso8601String(),
                       ];
                   })
                   ->toArray();
    }

  private function getRecentFlags(int $limit = 10): array
    {
        // 1. Change 'post.user' to 'flaggable.user' to match the Model
        return Flag::with(['flaggable.user', 'user'])
                    ->where('status', 'pending')
                    ->latest()
                    ->limit($limit)
                    ->get()
                    ->map(function ($flag) {
                        // Get the flagged item (Post, Comment, etc.)
                        $item = $flag->flaggable;

                        return [
                            'id' => $flag->id,
                            'reason' => $flag->reason,
                            // 2. Change post_id to flaggable_id
                            'post_id' => $flag->flaggable_id,
                            // 3. Safely access content (checks if item exists first)
                            'post_content' => $item && isset($item->content) 
                                ? substr($item->content, 0, 50) . '...' 
                                : 'Content unavailable',
                            'reported_by' => $flag->user?->name,
                            // 4. Access the author of the flagged item
                            'post_author' => $item?->user?->name ?? 'Unknown',
                            'created_at' => $flag->created_at->toIso8601String(),
                        ];
                    })
                    ->toArray();
    }

    private function getUserGrowthData(string $period): array
    {
        $days = $this->getPeriodDays($period);
        
        $data = User::select(
                        DB::raw('DATE(created_at) as date'),
                        DB::raw('COUNT(*) as count')
                    )
                    ->where('created_at', '>=', now()->subDays($days))
                    ->groupBy('date')
                    ->orderBy('date')
                    ->get()
                    ->map(function ($item) {
                        return [
                            'date' => $item->date,
                            'count' => $item->count,
                        ];
                    })
                    ->toArray();

        return $data;
    }

    private function getPostGrowthData(string $period): array
    {
        $days = $this->getPeriodDays($period);
        
        return Post::select(
                        DB::raw('DATE(created_at) as date'),
                        DB::raw('COUNT(*) as count')
                    )
                    ->where('created_at', '>=', now()->subDays($days))
                    ->groupBy('date')
                    ->orderBy('date')
                    ->get()
                    ->map(function ($item) {
                        return [
                            'date' => $item->date,
                            'count' => $item->count,
                        ];
                    })
                    ->toArray();
    }

    private function getUsersByCounty(): array
    {
        return DB::table('users')
                 ->join('wards', 'users.ward_id', '=', 'wards.id')
                 ->join('constituencies', 'wards.constituency_id', '=', 'constituencies.id')
                 ->join('counties', 'constituencies.county_id', '=', 'counties.id')
                 ->select('counties.name as county', DB::raw('COUNT(*) as count'))
                 ->groupBy('counties.id', 'counties.name')
                 ->orderByDesc('count')
                 ->get()
                 ->toArray();
    }

    private function getPostsByCounty(): array
    {
        return DB::table('posts')
                 ->join('wards', 'posts.ward_id', '=', 'wards.id')
                 ->join('constituencies', 'wards.constituency_id', '=', 'constituencies.id')
                 ->join('counties', 'constituencies.county_id', '=', 'counties.id')
                 ->select('counties.name as county', DB::raw('COUNT(*) as count'))
                 ->groupBy('counties.id', 'counties.name')
                 ->orderByDesc('count')
                 ->get()
                 ->toArray();
    }

    private function getUsersByVerification(): array
    {
        return [
            'verified' => User::whereNotNull('phone_verified_at')->count(),
            'unverified' => User::whereNull('phone_verified_at')->count(),
        ];
    }

    private function getPostsByType(): array
    {
        return [
            'text_only' => Post::whereNull('images')->whereNull('audio_path')->count(),
            'with_images' => Post::whereNotNull('images')->count(),
            'with_audio' => Post::whereNotNull('audio_path')->count(),
        ];
    }

    private function getEngagementTrend(string $period): array
    {
        $days = $this->getPeriodDays($period);
        
        $likes = Like::select(
                        DB::raw('DATE(created_at) as date'),
                        DB::raw('COUNT(*) as count')
                    )
                    ->where('created_at', '>=', now()->subDays($days))
                    ->groupBy('date')
                    ->orderBy('date')
                    ->get()
                    ->keyBy('date');

        $comments = Comment::select(
                        DB::raw('DATE(created_at) as date'),
                        DB::raw('COUNT(*) as count')
                    )
                    ->where('created_at', '>=', now()->subDays($days))
                    ->groupBy('date')
                    ->orderBy('date')
                    ->get()
                    ->keyBy('date');

        // Merge data
        $allDates = $likes->keys()->merge($comments->keys())->unique()->sort();
        
        return $allDates->map(function ($date) use ($likes, $comments) {
            return [
                'date' => $date,
                'likes' => $likes->get($date)?->count ?? 0,
                'comments' => $comments->get($date)?->count ?? 0,
            ];
        })->values()->toArray();
    }

    private function getTopPosts(int $limit = 10): array
    {
        return Post::withCount(['likes', 'comments'])
                   ->with('user')
                   ->orderByDesc('likes_count')
                   ->limit($limit)
                   ->get()
                   ->map(function ($post) {
                       return [
                           'id' => $post->id,
                           'content' => $post->content ? substr($post->content, 0, 100) . '...' : null,
                           'user' => $post->user?->name,
                           'likes' => $post->likes_count,
                           'comments' => $post->comments_count,
                           'engagement' => $post->likes_count + $post->comments_count,
                       ];
                   })
                   ->toArray();
    }

    private function getTopUsers(int $limit = 10): array
    {
        return User::withCount(['posts', 'comments', 'likes'])
                   ->orderByDesc('posts_count')
                   ->limit($limit)
                   ->get()
                   ->map(function ($user) {
                       return [
                           'id' => $user->id,
                           'name' => $user->name,
                           'posts' => $user->posts_count,
                           'comments' => $user->comments_count,
                           'likes' => $user->likes_count,
                           'total_activity' => $user->posts_count + $user->comments_count + $user->likes_count,
                       ];
                   })
                   ->toArray();
    }

    private function getPeriodDays(string $period): int
    {
        return match($period) {
            '7days' => 7,
            '30days' => 30,
            '90days' => 90,
            'year' => 365,
            default => 7,
        };
    }
}