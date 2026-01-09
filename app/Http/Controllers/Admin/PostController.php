<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PostController extends Controller
{
        //     Route::get('/', [AdminPostController::class, 'index']);
        // Route::get('/{post}', [AdminPostController::class, 'show']);
        // Route::delete('/{post}', [AdminPostController::class, 'destroy']);
        // Route::post('/{post}/restore', [AdminPostController::class, 'restore']);
    public function index()
    {
        // Retrieve and return a list of posts
        $posts = Post::all();
        return response()->json($posts);
        }

    public function show($id)
    {
        // Retrieve and return a specific post by ID
        $post = Post::findOrFail($id);
        return response()->json($post);
        }

    public function destroy($id)
    {
        // Delete the specified post
        $post = Post::findOrFail($id);
        $post->delete();
        return response()->json(['message' => 'Post deleted successfully.']);
        }
    public function restore($id)
    {
        // Restore the specified post
        $post = Post::withTrashed()->findOrFail($id);
        $post->restore();
        return response()->json(['message' => 'Post restored successfully.']);
    }
}