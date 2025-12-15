<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Flag;
use App\Models\Post;
use App\Models\Comment;
use Illuminate\Http\Request;

class ModerationController extends Controller
{
    /**
     * Get all flagged content.
     */
    public function index(Request $request)
    {
        $status = $request->get('status', 'pending');
        $type = $request->get('type'); // 'post' or 'comment'

        $query = Flag::with(['user', 'reviewer', 'flaggable'])
            ->where('status', $status)
            ->orderBy('created_at', 'desc');

        if ($type) {
            $flaggableType = $type === 'post' ? Post::class : Comment::class;
            $query->where('flaggable_type', $flaggableType);
        }

        $flags = $query->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $flags,
        ]);
    }

    /**
     * Get a single flag.
     */
    public function show($id)
    {
        $flag = Flag::with(['user', 'reviewer', 'flaggable.user'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $flag,
        ]);
    }

    /**
     * Review a flag (approve/reject).
     */
    public function review(Request $request, $id)
    {
        $request->validate([
            'action' => ['required', 'in:approve,dismiss'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $flag = Flag::findOrFail($id);
        $admin = $request->user();

        if ($request->action === 'approve') {
            // Deactivate the flagged content
            $flaggable = $flag->flaggable;
            $flaggable->update(['is_active' => false]);

            $flag->update([
                'status' => 'resolved',
                'reviewed_by' => $admin->id,
                'reviewed_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Flag approved. Content has been deactivated.',
                'data' => $flag->fresh(['reviewer']),
            ]);
        } else {
            // Dismiss the flag
            $flag->update([
                'status' => 'dismissed',
                'reviewed_by' => $admin->id,
                'reviewed_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Flag dismissed.',
                'data' => $flag->fresh(['reviewer']),
            ]);
        }
    }

    /**
     * Deactivate content directly.
     */
    public function deactivate(Request $request)
    {
        $request->validate([
            'type' => ['required', 'in:post,comment'],
            'id' => ['required', 'integer'],
        ]);

        $type = $request->type === 'post' ? Post::class : Comment::class;
        $content = $type::findOrFail($request->id);

        $content->update(['is_active' => false]);

        return response()->json([
            'success' => true,
            'message' => 'Content deactivated successfully.',
        ]);
    }

    /**
     * Activate content.
     */
    public function activate(Request $request)
    {
        $request->validate([
            'type' => ['required', 'in:post,comment'],
            'id' => ['required', 'integer'],
        ]);

        $type = $request->type === 'post' ? Post::class : Comment::class;
        $content = $type::findOrFail($request->id);

        $content->update(['is_active' => true]);

        return response()->json([
            'success' => true,
            'message' => 'Content activated successfully.',
        ]);
    }
}

