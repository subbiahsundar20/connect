<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\User;

class UserDirectoryController extends Controller
{
     public function index(Request $request)
    {
        $validated = $request->validate([
            'search' => ['sometimes', 'nullable', 'string', 'max:100'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:50'],
        ]);

        $search = trim($validated['search'] ?? '');
        $perPage = (int) ($validated['per_page'] ?? 10);

        $users = User::query()
            ->select([
                'id',
                'name',
                'bio',
                'profile_photo_path',
                'created_at',
            ])
            // Do not exclude the authenticated user.
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'LIKE', "%{$search}%")
                        ->orWhere('bio', 'LIKE', "%{$search}%")
                        ->orWhere('email', 'LIKE', "%{$search}%");
                });
            })
            ->orderBy('id', 'desc')
            ->paginate($perPage)
            ->withQueryString();

        $users->getCollection()->transform(function ($user) {
            $user->profile_photo_url = $user->profile_photo_path
                ? Storage::disk('public')->url($user->profile_photo_path)
                : null;

            return $user;
        });

        return response()->json([
            'success' => true,
            'message' => 'Users retrieved successfully.',
            'data' => $users,
        ]);
    }
}
