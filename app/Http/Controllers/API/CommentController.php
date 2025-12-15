<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CommentController extends Controller
{
    /**
     * Get comments for a post.
     */
    public function index(Request $request, $postId)
    {
        $post = Post::findOrFail($postId);

        $comments = Comment::with(['user.county', 'user.constituency', 'user.ward', 'replies.user'])
            ->where('post_id', $postId)
            ->whereNull('parent_id')
            ->where('is_active', true)
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $comments,
        ]);
    }

    /**
     * Create a new comment.
     */
    public function store(Request $request, $postId)
    {
        $request->validate([
            'content' => ['required', 'string', 'max:2000'],
            'parent_id' => ['nullable', 'exists:comments,id'],
        ]);

        $post = Post::findOrFail($postId);

        $comment = Comment::create([
            'post_id' => $postId,
            'user_id' => $request->user()->id,
            'content' => $request->content,
            'parent_id' => $request->parent_id,
        ]);

        // Increment comments count
        $post->increment('comments_count');

        return response()->json([
            'success' => true,
            'message' => 'Comment created successfully.',
            'data' => $comment->load(['user.county', 'user.constituency', 'user.ward']),
        ], 201);
    }

    /**
     * Update a comment.
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'content' => ['required', 'string', 'max:2000'],
        ]);

        $comment = Comment::where('user_id', $request->user()->id)
            ->findOrFail($id);

        $comment->update([
            'content' => $request->content,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Comment updated successfully.',
            'data' => $comment->load(['user.county', 'user.constituency', 'user.ward']),
        ]);
    }

    /**
     * Delete a comment.
     */
    public function destroy(Request $request, $id)
    {
        $comment = Comment::where('user_id', $request->user()->id)
            ->findOrFail($id);

        // Decrement comments count
        $comment->post->decrement('comments_count');

        $comment->delete();

        return response()->json([
            'success' => true,
            'message' => 'Comment deleted successfully.',
        ]);
    }
}

