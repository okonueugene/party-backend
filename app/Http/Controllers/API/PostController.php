<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreatePostRequest;
use App\Models\Post;
use App\Services\MediaService;
use Illuminate\Http\Request;

class PostController extends Controller
{
    protected MediaService $mediaService;

    public function __construct(MediaService $mediaService)
    {
        $this->mediaService = $mediaService;
    }

    /**
     * Get posts feed.
     */
 public function index(Request $request)
{
    $perPage = $request->get('per_page', 3);
    $authenticatedUser = $request->user(); // Get the currently logged-in user

    // 1. Determine the geographic filter source
    // Priority: Request Parameter > Authenticated User's Location > Default (Public/All)
    
    $wardId         = $request->get('ward_id', $authenticatedUser->ward_id);
    $constituencyId = $request->get('constituency_id', $authenticatedUser->constituency_id);
    $countyId       = $request->get('county_id', $authenticatedUser->county_id);

    // 2. Build the query
    $query = Post::with([
            'user.county', 'user.constituency', 'user.ward', 
            'county', 'constituency', 'ward'
        ])
        ->where('is_active', true)
        ->where('is_flagged', false)
        ->orderBy('created_at', 'desc');

    // 3. Apply Filtering (The most specific filter wins)
    if ($wardId) {
        // Highly personalized feed
        $query->where('ward_id', $wardId);
    } elseif ($constituencyId) {
        // Slightly broader feed (e.g., all posts in the user's constituency)
        $query->where('constituency_id', $constituencyId);
    } elseif ($countyId) {
        // Broadest localized feed
        $query->where('county_id', $countyId);
    }
    // If none of the above are set (e.g., user hasn't completed registration),
    // the query runs without location filters, showing a general feed.

    $posts = $query->paginate($perPage);

    return response()->json([
        'success' => true,
        'data' => $posts,
    ]);
}

    /**
     * Create a new post.
     */
    public function store(CreatePostRequest $request)
    {
        $data = [
            'user_id' => $request->user()->id,
            'content' => $request->content,
        ];

        // Handle image upload
        if ($request->hasFile('image')) {
            $imageResult = $this->mediaService->uploadImage($request->file('image'));
            if ($imageResult['success']) {
                $data['image'] = $imageResult['path'];
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $imageResult['message'],
                ], 400);
            }
        }

        // Handle audio upload
        if ($request->hasFile('audio')) {
            $audioResult = $this->mediaService->uploadAudio($request->file('audio'));
            if ($audioResult['success']) {
                $data['audio'] = $audioResult['path'];
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $audioResult['message'],
                ], 400);
            }
        }

        // Add geographic data if provided
        if ($request->county_id) {
            $data['county_id'] = $request->county_id;
        }
        if ($request->constituency_id) {
            $data['constituency_id'] = $request->constituency_id;
        }
        if ($request->ward_id) {
            $data['ward_id'] = $request->ward_id;
        }

        // If no geographic data provided, use user's location
        if (!$request->county_id && $request->user()->county_id) {
            $data['county_id'] = $request->user()->county_id;
            $data['constituency_id'] = $request->user()->constituency_id;
            $data['ward_id'] = $request->user()->ward_id;
        }

        $post = Post::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Post created successfully.',
            'data' => $post->load(['user.county', 'user.constituency', 'user.ward', 'county', 'constituency', 'ward']),
        ], 201);
    }

    /**
     * Get a single post.
     */
    public function show($id)
    {
        $post = Post::with([
            'user.county', 'user.constituency', 'user.ward',
            'county', 'constituency', 'ward',
            'comments.user',
            'likes.user',
        ])
            ->where('is_active', true)
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $post,
        ]);
    }

    /**
     * Update a post.
     */
    public function update(CreatePostRequest $request, $id)
    {
        $post = Post::where('user_id', $request->user()->id)
            ->findOrFail($id);

        $data = [];

        if ($request->has('content')) {
            $data['content'] = $request->content;
        }

        // Handle image upload
        if ($request->hasFile('image')) {
            // Delete old image
            if ($post->image) {
                $this->mediaService->deleteFile($post->image);
            }

            $imageResult = $this->mediaService->uploadImage($request->file('image'));
            if ($imageResult['success']) {
                $data['image'] = $imageResult['path'];
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $imageResult['message'],
                ], 400);
            }
        }

        // Handle audio upload
        if ($request->hasFile('audio')) {
            // Delete old audio
            if ($post->audio) {
                $this->mediaService->deleteFile($post->audio);
            }

            $audioResult = $this->mediaService->uploadAudio($request->file('audio'));
            if ($audioResult['success']) {
                $data['audio'] = $audioResult['path'];
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $audioResult['message'],
                ], 400);
            }
        }

        $post->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Post updated successfully.',
            'data' => $post->load(['user.county', 'user.constituency', 'user.ward', 'county', 'constituency', 'ward']),
        ]);
    }

    /**
     * Delete a post.
     */
    public function destroy(Request $request, $id)
    {
        $post = Post::where('user_id', $request->user()->id)
            ->findOrFail($id);

        // Delete associated media
        if ($post->image) {
            $this->mediaService->deleteFile($post->image);
        }
        if ($post->audio) {
            $this->mediaService->deleteFile($post->audio);
        }

        $post->delete();

        return response()->json([
            'success' => true,
            'message' => 'Post deleted successfully.',
        ]);
    }
}

