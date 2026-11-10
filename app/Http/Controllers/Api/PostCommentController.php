<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\PostComment;
use Illuminate\Http\Request;

class PostCommentController extends Controller
{
    /**
     * Create a comment for a post
     */
    public function store(Request $request, Post $post)
    {
        $validated = $request->validate([
            'content' => ['required', 'string', 'max:5000'],
        ]);

        $comment = $post->comments()->create([
            'user_id' => $request->user()->id,
            'content' => $validated['content'],
        ]);

        $comment->load('user:id,name,profile_photo_path');

        return response()->json([
            'success' => true,
            'message' => 'Comment added successfully.',
            'data' => [
                'comment' => $comment,
                'comments_count' => $post->comments()->count(),
            ],
        ], 201);
    }


    /**
     * Get all comments for a particular post
     */
    public function index(Request $request, Post $post)
    {
        $validated = $request->validate([
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $perPage = $validated['per_page'] ?? 20;

        $comments = $post->comments()
            ->with('user:id,name,profile_photo_path')
            ->oldest()
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Comments retrieved successfully.',
            'data' => [
                'post_id' => $post->id,
                'comments_count' => $post->comments()->count(),
                'comments' => $comments,
            ],
        ]);
    }


    /**
     * Update an existing comment
     */
    public function update(
        Request $request,
        Post $post,
        $commentId
    ) {
        $validated = $request->validate([
            'content' => ['required', 'string', 'max:5000'],
        ]);

        $comment = $post->comments()
            ->where('id', $commentId)
            ->firstOrFail();

        // Only the comment owner can update it
        if ($comment->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to update this comment.',
            ], 403);
        }

        $comment->update([
            'content' => $validated['content'],
        ]);

        $comment->load('user:id,name,profile_photo_path');

        return response()->json([
            'success' => true,
            'message' => 'Comment updated successfully.',
            'data' => [
                'comment' => $comment,
                'comments_count' => $post->comments()->count(),
            ],
        ]);
    }


    /**
     * Delete an existing comment
     */
    public function destroy(
        Request $request,
        Post $post,
        $commentId
    ) {
        $comment = $post->comments()
            ->where('id', $commentId)
            ->firstOrFail();

        // Only the comment owner can delete it
        if ($comment->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to delete this comment.',
            ], 403);
        }

        $comment->delete();

        return response()->json([
            'success' => true,
            'message' => 'Comment deleted successfully.',
            'data' => [
                'post_id' => $post->id,
                'comments_count' => $post->comments()->count(),
            ],
        ]);
    }
}