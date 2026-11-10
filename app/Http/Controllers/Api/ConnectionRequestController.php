<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ConnectionRequest;
use App\Models\User;
use Illuminate\Http\Request;

class ConnectionRequestController extends Controller
{
    // API 1: Send connection request
    public function store(Request $request)
    {
        $validated = $request->validate([
            'receiver_id' => ['required', 'integer', 'exists:users,id'],
        ]);
    
        $senderId = $request->user()->id;
        $receiverId = $validated['receiver_id'];

        if ($senderId == $receiverId) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot send a connection request to yourself.',
            ], 422);
        }

        $existingRequest = ConnectionRequest::where(
            'sender_id',
            $senderId
        )->where(
            'receiver_id',
            $receiverId
        )->first();

        if ($existingRequest) {

            if ($existingRequest->status === 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Connection request is already pending.',
                ], 409);
            }

            if ($existingRequest->status === 'approved') {
                return response()->json([
                    'success' => false,
                    'message' => 'You are already connected.',
                ], 409);
            }

            // Allow resending a previously rejected request.
            $existingRequest->update([
                'status' => 'pending',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Connection request sent successfully.',
                'data' => $existingRequest,
            ]);
        }

        $connectionRequest = ConnectionRequest::create([
            'sender_id' => $senderId,
            'receiver_id' => $receiverId,
            'status' => 'pending',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Connection request sent successfully.',
            'data' => $connectionRequest,
        ], 201);
    }


    // API 2: Get received connection requests
    public function received(Request $request)
    {
        $requests = ConnectionRequest::with([
            'sender:id,name,profile_photo_path',
        ])
            ->where('receiver_id', $request->user()->id)
            ->latest()
            ->paginate(10);

        $requests->getCollection()->transform(function ($connectionRequest) {

            return [
                'request_id' => $connectionRequest->id,
                'user_id' => $connectionRequest->sender->id,
                'name' => $connectionRequest->sender->name,
                'profile_photo_path' => $connectionRequest->sender->profile_photo_path,
                'status' => $connectionRequest->status,
                'created_at' => $connectionRequest->created_at,
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Connection requests retrieved successfully.',
            'data' => $requests,
        ]);
    }


    // API 3: Approve or reject connection request
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'status' => ['required', 'in:approved,rejected'],
        ]);

        $connectionRequest = ConnectionRequest::where('id', $id)
            ->where('receiver_id', $request->user()->id)
            ->first();

        if (!$connectionRequest) {
            return response()->json([
                'success' => false,
                'message' => 'Connection request not found.',
            ], 404);
        }

        if ($connectionRequest->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'This connection request has already been processed.',
            ], 409);
        }

        $connectionRequest->update([
            'status' => $validated['status'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Connection request ' . $validated['status'] . ' successfully.',
            'data' => $connectionRequest,
        ]);
    }
}