<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
     // View the logged-in user's profile
    public function show(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'success' => true,
            'message' => 'Profile retrieved successfully.',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'bio' => $user->bio,
                'profile_photo_path' => $user->profile_photo_path,
                'profile_photo_url' => $user->profile_photo_path
                  ? \Illuminate\Support\Facades\Storage::disk('public')
                     ->url($user->profile_photo_path)
                         : null,
                'created_at' => $user->created_at,
            ],
        ]);
    }

    // Update the logged-in user's profile
    public function update(Request $request)
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'bio' => ['sometimes', 'nullable', 'string', 'max:500'],
        ]);

        $request->user()->update($validated);

        $user = $request->user()->fresh();

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully.',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'bio' => $user->bio,
                'profile_photo_path' => $user->profile_photo_path,
            ],
        ]);
    }

    // View another user's public profile
     public function publicProfile($user)
    {
        $profile = \App\Models\User::query()
            ->select([
                'id',
                'name',
                'bio',
                'profile_photo_path',
                'created_at',
            ])
            ->findOrFail($user);

        $profile->profile_photo_url = $profile->profile_photo_path
            ? \Illuminate\Support\Facades\Storage::disk('public')
                ->url($profile->profile_photo_path)
            : null;

        $posts = \App\Models\Post::where('user_id', $profile->id)
            ->with('images')
            ->latest()
            ->paginate(10);

        $posts->getCollection()->transform(function ($post) {
            $post->images->transform(function ($image) {
                $image->image_url = \Illuminate\Support\Facades\Storage::disk('public')
                    ->url($image->image_path);

                return $image;
            });

            return $post;
        });

        return response()->json([
            'success' => true,
            'message' => 'User profile retrieved successfully.',
            'data' => [
                'user' => $profile,
                'posts' => $posts,
            ],
        ]);
    }
    
public function updatePhoto(Request $request)
{
    $validated = $request->validate([
        'photo' => [
            'required',
            'image',
            'mimes:jpg,jpeg,png,webp',
            'max:5120',
        ],
    ]);

    $user = $request->user();

    // Save the new photo.
    $path = $validated['photo']->store(
        'profile-photos',
        'public'
    );

    // Remove the previous photo after the new one is stored.
    $oldPath = $user->profile_photo_path;

    $user->update([
        'profile_photo_path' => $path,
    ]);

    if ($oldPath) {
        Storage::disk('public')->delete($oldPath);
    }

    return response()->json([
        'success' => true,
        'message' => 'Profile photo updated successfully.',
        'profile_photo_path' => $path,
        'profile_photo_url' => Storage::disk('public')->url($path),
    ]);
}


}
