<?php

namespace App\Http\Controllers;

use App\Models\Connection;
use App\Models\ConnectionRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ConnectionController extends Controller
{
    public function index(Request $request)
    {
        $currentUser = Auth::user();
        $users = User::where('id', '!=', $currentUser->id)
            ->orderBy('created_at', 'desc')
            ->get();

        if ($request->filled('search')) {
            $term = mb_strtolower($request->search);
            $users = $users->filter(function (User $user) use ($term) {
                return str_contains(mb_strtolower((string) $user->name), $term)
                    || str_contains(mb_strtolower((string) $user->email), $term)
                    || str_contains(mb_strtolower((string) $user->designation), $term)
                    || str_contains(mb_strtolower((string) $user->user_type), $term);
            })->values();
        }

        $states = [];
        foreach ($users as $user) {
            $states[$user->id] = $this->connectionState($currentUser->id, $user->id);
        }

        return view('people.index', compact('users', 'states'));
    }

    public function sendRequest(int $userId)
    {
        $sender = Auth::user();
        $receiver = User::findOrFail($userId);

        if ($sender->id === $receiver->id) {
            return back()->with('error', 'You cannot connect with yourself.');
        }

        if ($sender->isConnectedWith($receiver->id)) {
            return back()->with('error', 'You are already connected.');
        }

        $incoming = ConnectionRequest::where([
            'sender_id' => $receiver->id,
            'receiver_id' => $sender->id,
            'status' => ConnectionRequest::STATUS_PENDING,
        ])->first();

        if ($incoming) {
            return redirect()->route('connections.pending')->with('error', 'This user already sent you a request. Please respond to it.');
        }

        $existing = ConnectionRequest::where([
            'sender_id' => $sender->id,
            'receiver_id' => $receiver->id,
            'status' => ConnectionRequest::STATUS_PENDING,
        ])->first();

        if ($existing) {
            return back()->with('error', 'Connection request already sent.');
        }

        ConnectionRequest::create([
            'sender_id' => $sender->id,
            'receiver_id' => $receiver->id,
            'status' => ConnectionRequest::STATUS_PENDING,
        ]);

        return back()->with('success', 'Connection request sent.');
    }

    public function acceptRequest(int $requestId)
    {
        $requestModel = ConnectionRequest::findOrFail($requestId);
        abort_unless($requestModel->receiver_id === Auth::id(), 403);
        abort_unless($requestModel->status === ConnectionRequest::STATUS_PENDING, 403);

        DB::transaction(function () use ($requestModel) {
            $requestModel->update(['status' => ConnectionRequest::STATUS_ACCEPTED]);
            Connection::createForUsers($requestModel->sender_id, $requestModel->receiver_id, $requestModel->id);
        });

        return back()->with('success', 'Connection request accepted.');
    }

    public function rejectRequest(int $requestId)
    {
        $requestModel = ConnectionRequest::findOrFail($requestId);
        abort_unless($requestModel->receiver_id === Auth::id(), 403);
        abort_unless($requestModel->status === ConnectionRequest::STATUS_PENDING, 403);

        $requestModel->update(['status' => ConnectionRequest::STATUS_REJECTED]);

        return back()->with('success', 'Connection request rejected.');
    }

    public function cancelRequest(int $requestId)
    {
        $requestModel = ConnectionRequest::findOrFail($requestId);
        abort_unless($requestModel->sender_id === Auth::id(), 403);
        abort_unless($requestModel->status === ConnectionRequest::STATUS_PENDING, 403);

        $requestModel->update(['status' => ConnectionRequest::STATUS_CANCELLED]);

        return back()->with('success', 'Connection request cancelled.');
    }

    public function myConnections()
    {
        $connections = Connection::with(['userOne', 'userTwo'])
            ->where('user_one_id', Auth::id())
            ->orWhere('user_two_id', Auth::id())
            ->latest()
            ->get();

        return view('connections.index', compact('connections'));
    }

    public function pendingRequests()
    {
        $requests = ConnectionRequest::with('sender')
            ->where('receiver_id', Auth::id())
            ->where('status', ConnectionRequest::STATUS_PENDING)
            ->latest()
            ->get();

        return view('connections.pending', compact('requests'));
    }

    private function connectionState(int $currentUserId, int $otherUserId): array
    {
        [$one, $two] = Connection::sortedPair($currentUserId, $otherUserId);
        if (Connection::where('user_one_id', $one)->where('user_two_id', $two)->exists()) {
            return ['status' => 'connected'];
        }

        $sent = ConnectionRequest::where([
            'sender_id' => $currentUserId,
            'receiver_id' => $otherUserId,
            'status' => ConnectionRequest::STATUS_PENDING,
        ])->first();

        if ($sent) {
            return ['status' => 'sent', 'request' => $sent];
        }

        $received = ConnectionRequest::where([
            'sender_id' => $otherUserId,
            'receiver_id' => $currentUserId,
            'status' => ConnectionRequest::STATUS_PENDING,
        ])->first();

        if ($received) {
            return ['status' => 'received', 'request' => $received];
        }

        return ['status' => 'none'];
    }
}
