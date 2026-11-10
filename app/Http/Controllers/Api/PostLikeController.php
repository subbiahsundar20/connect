<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\PostLike;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PostLikeController extends Controller
{
    /**
     * Like a post
     */
    public function like(Request $request, Post $post)
    {
        $userId = $request->user()->id;

        DB::transaction(function () use ($post, $userId) {

            // Lock the post while updating its like count
            $lockedPost = Post::where('id', $post->id)
                ->lockForUpdate()
                ->firstOrFail();

            // Check whether the user already liked this post
            $alreadyLiked = PostLike::where('post_id', $lockedPost->id)
                ->where('user_id', $userId)
                ->exists();

            if (!$alreadyLiked) {

                PostLike::create([
                    'post_id' => $lockedPost->id,
                    'user_id' => $userId,
                ]);

                // Increase like count
                $lockedPost->increment('likes_count');
            }
        });

        return $this->likeResponse($post, $userId, 'Post liked successfully.');
    }


    /**
     * Dislike (remove like from) a post
     */
    public function dislike(Request $request, Post $post)
    {
        $userId = $request->user()->id;

        DB::transaction(function () use ($post, $userId) {

            // Lock the post while updating its like count
            $lockedPost = Post::where('id', $post->id)
                ->lockForUpdate()
                ->firstOrFail();

            // Delete only the current user's like
            $deleted = PostLike::where('post_id', $lockedPost->id)
                ->where('user_id', $userId)
                ->delete();

            // Decrease count only if a like was deleted
            if ($deleted > 0 && $lockedPost->likes_count > 0) {
                $lockedPost->decrement('likes_count');
            }
        });

        return $this->likeResponse($post, $userId, 'Post disliked successfully.');
    }


    /**
     * Return updated like count and users who liked the post
     */
    private function likeResponse(Post $post, int $userId, string $message)
    {
        $post->refresh();

        $likes = $post->likes()
            ->with('user:id,name,profile_photo_path')
            ->latest()
            ->get()
            ->map(function ($like) {
                return [
                    'user_id' => $like->user->id,
                    'name' => $like->user->name,
                    'profile_photo_path' => $like->user->profile_photo_path,
                    'liked_at' => $like->created_at,
                ];
            });

        $isLiked = $post->likes()
            ->where('user_id', $userId)
            ->exists();

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => [
                'post_id' => $post->id,
                'likes_count' => $post->likes_count,
                'is_liked_by_current_user' => $isLiked,
                'liked_users' => $likes,
            ],
        ], 200);
    }
}