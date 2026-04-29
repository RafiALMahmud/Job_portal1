<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Services\Crypto\CustomECC;
use App\Services\Crypto\ECCKeyManager;
use App\Services\Crypto\RecordMac;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MessageController extends Controller
{
    public function __construct(
        private readonly ECCKeyManager $keys,
        private readonly CustomECC $ecc,
        private readonly RecordMac $mac
    ) {
    }

    public function index()
    {
        $user = Auth::user();
        $this->keys->activeKeyForUser($user);

        $conversations = Conversation::with(['userOne', 'userTwo', 'messages' => fn ($query) => $query->latest()])
            ->where('user_one_id', $user->id)
            ->orWhere('user_two_id', $user->id)
            ->latest('updated_at')
            ->get()
            ->filter(fn (Conversation $conversation) => $conversation->otherUser($user->id)?->id
                && $user->isConnectedWith($conversation->otherUser($user->id)->id))
            ->values();

        $users = User::where('id', '!=', $user->id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->filter(fn (User $candidate) => $user->isConnectedWith($candidate->id))
            ->values();

        return view('messages.index', compact('conversations', 'users'));
    }

    public function show(int $userId)
    {
        $currentUser = Auth::user();
        $otherUser = User::findOrFail($userId);

        abort_if($currentUser->id === $otherUser->id, 403);
        if (!$currentUser->canMessage($otherUser->id)) {
            return redirect()->route('people.index')->with('error', 'You can only message your connections.');
        }

        $this->keys->activeKeyForUser($currentUser);
        $this->keys->activeKeyForUser($otherUser);

        $conversation = Conversation::between($currentUser->id, $otherUser->id);
        abort_unless($conversation->hasParticipant($currentUser->id), 403);

        $messages = $conversation->messages()
            ->with(['sender', 'receiver'])
            ->orderBy('created_at')
            ->get()
            ->map(function (Message $message) use ($currentUser) {
                $message->decrypted_body = $this->decryptForUser($message, $currentUser->id);
                return $message;
            });

        Message::where('conversation_id', $conversation->id)
            ->where('receiver_id', $currentUser->id)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return view('messages.show', compact('conversation', 'messages', 'otherUser'));
    }

    public function store(Request $request, int $userId)
    {
        $request->validate([
            'body' => 'required|string|max:1000',
        ]);

        $sender = Auth::user();
        $receiver = User::findOrFail($userId);
        abort_if($sender->id === $receiver->id, 403);
        if (!$sender->canMessage($receiver->id)) {
            return redirect()->route('people.index')->with('error', 'You can only message your connections.');
        }

        $senderKey = $this->keys->activeKeyForUser($sender);
        $receiverKey = $this->keys->activeKeyForUser($receiver);
        $conversation = Conversation::between($sender->id, $receiver->id);

        // Two ECC ElGamal ciphertexts are stored: one encrypted to the sender's
        // public key and one encrypted to the receiver's public key. This lets
        // both participants read their own copy later without storing plaintext.
        $senderPayload = $this->ecc->encrypt($request->body, $this->keys->publicPoint($senderKey));
        $senderPayload['sender_key_id'] = $senderKey->id;
        $senderJson = json_encode($senderPayload);

        $receiverPayload = $this->ecc->encrypt($request->body, $this->keys->publicPoint($receiverKey));
        $receiverPayload['receiver_key_id'] = $receiverKey->id;
        $receiverJson = json_encode($receiverPayload);

        Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $sender->id,
            'receiver_id' => $receiver->id,
            'sender_ecc_key_id' => $senderKey->id,
            'receiver_ecc_key_id' => $receiverKey->id,
            'sender_encrypted_body' => $senderJson,
            'sender_mac' => $this->mac->sign($senderJson),
            'receiver_encrypted_body' => $receiverJson,
            'receiver_mac' => $this->mac->sign($receiverJson),
            'encryption_algorithm' => 'CUSTOM_ECC_ELGAMAL',
            'is_read' => false,
        ]);

        $conversation->touch();

        return redirect()->route('messages.show', $receiver->id)->with('success', 'Message sent securely.');
    }

    public function markAsRead(int $messageId)
    {
        $message = Message::findOrFail($messageId);
        abort_unless($message->receiver_id === Auth::id(), 403);

        $message->update(['is_read' => true]);

        return response()->json(['success' => true]);
    }

    private function decryptForUser(Message $message, int $userId): string
    {
        try {
            if ($message->sender_id === $userId) {
                $key = $this->keys->findKey($message->sender_ecc_key_id);
                abort_unless($key->user_id === $userId, 403);
                if (!$this->mac->verify($message->sender_encrypted_body, $message->sender_mac)) {
                    return 'Encrypted data integrity check failed. This record may have been modified.';
                }
                return $this->ecc->decrypt(json_decode($message->sender_encrypted_body, true), $this->keys->privateScalar($key));
            }

            if ($message->receiver_id === $userId) {
                $key = $this->keys->findKey($message->receiver_ecc_key_id);
                abort_unless($key->user_id === $userId, 403);
                if (!$this->mac->verify($message->receiver_encrypted_body, $message->receiver_mac)) {
                    return 'Encrypted data integrity check failed. This record may have been modified.';
                }
                return $this->ecc->decrypt(json_decode($message->receiver_encrypted_body, true), $this->keys->privateScalar($key));
            }
        } catch (\Throwable) {
            return '[Encrypted message could not be decrypted. It may be tampered or the key may be unavailable.]';
        }

        abort(403);
    }
}
