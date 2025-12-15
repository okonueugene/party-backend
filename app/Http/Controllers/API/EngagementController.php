<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\Comment;
use App\Models\Like;
use App\Models\Share;
use App\Models\Flag;
use Illuminate\Http\Request;

class EngagementController extends Controller
{
    /**
     * Like/Unlike a post or comment.
     */
    public function toggleLike(Request $request)
    {
        $request->validate([
            'likeable_type' => ['required', 'in:post,comment'],
            'likeable_id' => ['required', 'integer'],
        ]);

        $user = $request->user();
        $likeableType = $request->likeable_type === 'post' ? Post::class : Comment::class;
        $likeableId = $request->likeable_id;

        // Find the likeable model
        $likeable = $likeableType::findOrFail($likeableId);

        // Check if like already exists
        $like = Like::where('user_id', $user->id)
            ->where('likeable_type', $likeableType)
            ->where('likeable_id', $likeableId)
            ->first();

        if ($like) {
            // Unlike
            $like->delete();
            $likeable->decrement('likes_count');

            return response()->json([
                'success' => true,
                'message' => 'Unliked successfully.',
                'data' => ['liked' => false],
            ]);
        } else {
            // Like
            Like::create([
                'user_id' => $user->id,
                'likeable_type' => $likeableType,
                'likeable_id' => $likeableId,
            ]);
            $likeable->increment('likes_count');

            return response()->json([
                'success' => true,
                'message' => 'Liked successfully.',
                'data' => ['liked' => true],
            ]);
        }
    }

    /**
     * Share a post.
     */
    public function share(Request $request, $postId)
    {
        $post = Post::findOrFail($postId);
        $user = $request->user();

        // Check if already shared
        $share = Share::where('user_id', $user->id)
            ->where('post_id', $postId)
            ->first();

        if ($share) {
            return response()->json([
                'success' => false,
                'message' => 'Post already shared.',
            ], 400);
        }

        Share::create([
            'user_id' => $user->id,
            'post_id' => $postId,
        ]);

        $post->increment('shares_count');

        return response()->json([
            'success' => true,
            'message' => 'Post shared successfully.',
        ]);
    }

    /**
     * Flag/Report a post or comment.
     */
    public function flag(Request $request)
    {
        $request->validate([
            'flaggable_type' => ['required', 'in:post,comment'],
            'flaggable_id' => ['required', 'integer'],
            'reason' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $user = $request->user();
        $flaggableType = $request->flaggable_type === 'post' ? Post::class : Comment::class;
        $flaggableId = $request->flaggable_id;

        // Find the flaggable model
        $flaggable = $flaggableType::findOrFail($flaggableId);

        // Check if already flagged by this user
        $existingFlag = Flag::where('user_id', $user->id)
            ->where('flaggable_type', $flaggableType)
            ->where('flaggable_id', $flaggableId)
            ->first();

        if ($existingFlag) {
            return response()->json([
                'success' => false,
                'message' => 'You have already flagged this content.',
            ], 400);
        }

        $flag = Flag::create([
            'user_id' => $user->id,
            'flaggable_type' => $flaggableType,
            'flaggable_id' => $flaggableId,
            'reason' => $request->reason,
            'description' => $request->description,
            'status' => 'pending',
        ]);

        // Increment flags count
        $flaggable->increment('flags_count');

        // If flags count reaches threshold, mark as flagged
        if ($flaggable->flags_count >= 5) {
            $flaggable->update(['is_flagged' => true]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Content flagged successfully. Our moderators will review it.',
            'data' => $flag,
        ], 201);
    }
}

