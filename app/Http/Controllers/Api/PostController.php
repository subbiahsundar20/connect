<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Post;
use App\Models\PostImage;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PostController extends Controller
{
    // Create a post with optional images.
    public function store(Request $request)
    {
        $validated = $request->validate([
            'content' => ['nullable', 'string', 'max:10000', 'required_without:images'],
            'images' => ['sometimes', 'array', 'max:5'],
            'images.*' => [
                'required',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:5120',
            ],
        ], [
            'content.required_without' => 'Enter post content or attach at least one image.',
            'images.max' => 'You can upload a maximum of 5 images per post.',
            'images.*.image' => 'Each uploaded file must be an image.',
            'images.*.max' => 'Each image must be 5 MB or smaller.',
        ]);

        $post = null;
        $storedPaths = [];

        try {
            DB::transaction(function () use (
                $request,
                $validated,
                &$post,
                &$storedPaths
            ) {
                $post = Post::create([
                    'user_id' => $request->user()->id,
                    'content' => $validated['content'] ?? null,
                ]);

                foreach ($request->file('images', []) as $image) {
                    $path = $image->store('post-images', 'public');
                    $storedPaths[] = $path;

                    $post->images()->create([
                        'image_path' => $path,
                    ]);
                }
            });
        } catch (\Throwable $e) {
            foreach ($storedPaths as $path) {
                Storage::disk('public')->delete($path);
            }

            throw $e;
        }

        $post->load(['images', 'user']);

        return response()->json([
            'success' => true,
            'message' => 'Post created successfully.',
            'data' => $this->formatPost($post),
        ], 201);
    }

    // List posts created by a specific user.
    public function userPosts(int $userId)
    {
        $user = User::findOrFail($userId);

        $posts = $user->posts()
            ->with(['images', 'user'])
            ->latest()
            ->paginate(10);

        $posts->getCollection()->transform(function ($post) {
            return $this->formatPost($post);
        });

        return response()->json([
            'success' => true,
            'message' => 'User posts retrieved successfully.',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
            ],
            'data' => $posts,
        ]);
    }

    private function formatPost(Post $post): array
    {
        return [
            'id' => $post->id,
            'content' => $post->content,
            'created_at' => $post->created_at,
            'user' => [
                'id' => $post->user->id,
                'name' => $post->user->name,
                'profile_photo_url' => $post->user->profile_photo_path
                    ? Storage::disk('public')->url(
                        $post->user->profile_photo_path
                    )
                    : null,
            ],
            'images' => $post->images->map(function ($image) {
                return [
                    'id' => $image->id,
                    'image_url' => Storage::disk('public')->url(
                        $image->image_path
                    ),
                ];
            })->values(),
        ];
    }
}
