@extends('front.layouts.app')

@section('customCss')
<style>
    .chat-wrapper {
        max-width: 1100px;
        margin: 30px auto;
        padding: 0 20px;
    }

    .chat-card {
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 14px;
        box-shadow: 0 8px 24px rgba(15, 23, 42, 0.08);
        padding: 24px;
    }

    .chat-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 16px;
        margin-bottom: 18px;
    }

    .chat-header-user {
        display: flex;
        align-items: center;
        gap: 14px;
        min-width: 0;
    }

    .chat-header-avatar {
        width: 58px;
        height: 58px;
        border-radius: 50%;
        object-fit: cover;
        border: 2px solid #e5e7eb;
        background: #f3f4f6;
        flex: 0 0 auto;
    }

    .chat-title {
        font-size: 28px;
        font-weight: 700;
        color: #111827;
        margin-bottom: 6px;
    }

    .chat-subtitle {
        font-size: 15px;
        color: #6b7280;
        margin-bottom: 0;
    }

    .secure-alert {
        background: #dcfce7;
        color: #166534;
        border: 1px solid #86efac;
        border-radius: 10px;
        padding: 14px 18px;
        margin-bottom: 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .secure-alert .btn-close {
        margin-left: 16px;
    }

    .messages-box {
        height: 420px;
        overflow-y: auto;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        background: #f8fafc;
        padding: 20px;
        margin-bottom: 20px;
    }

    .messages-list {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .message-row {
        display: flex;
        width: 100%;
    }

    .message-row.sent {
        justify-content: flex-end;
    }

    .message-row.received {
        justify-content: flex-start;
    }

    .message-with-avatar {
        display: flex;
        align-items: flex-end;
        gap: 8px;
        max-width: 72%;
    }

    .message-row.sent .message-with-avatar {
        flex-direction: row-reverse;
    }

    .message-avatar {
        width: 34px;
        height: 34px;
        border-radius: 50%;
        object-fit: cover;
        border: 1px solid #e5e7eb;
        background: #f3f4f6;
        flex: 0 0 auto;
    }

    .message-bubble {
        max-width: 100%;
        padding: 12px 16px;
        border-radius: 16px;
        word-break: break-word;
        line-height: 1.45;
        font-size: 15px;
    }

    .message-row.sent .message-bubble {
        background: #2563eb;
        color: #ffffff;
        border-bottom-right-radius: 4px;
    }

    .message-row.received .message-bubble {
        background: #ffffff;
        color: #111827;
        border: 1px solid #e5e7eb;
        border-bottom-left-radius: 4px;
    }

    .message-text {
        margin-bottom: 6px;
        color: inherit;
    }

    .message-time {
        font-size: 12px;
    }

    .message-row.sent .message-time {
        color: rgba(255, 255, 255, 0.85);
    }

    .message-row.received .message-time {
        color: #6b7280;
    }

    .message-form {
        display: flex;
        gap: 12px;
        align-items: center;
        width: 100%;
    }

    .message-input {
        flex: 1;
        width: 100%;
        height: 88px;
        min-height: 88px;
        resize: vertical;
        border: 1px solid #d1d5db;
        border-radius: 10px;
        padding: 12px 14px;
        font-size: 15px;
        color: #111827;
        background: #ffffff;
    }

    .message-input::placeholder {
        color: #9ca3af;
    }

    .message-input:focus {
        outline: none;
        border-color: #2563eb;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
    }

    .send-button {
        background: #16a34a;
        color: #ffffff;
        border: none;
        border-radius: 10px;
        height: 64px;
        padding: 0 22px;
        font-weight: 600;
        cursor: pointer;
        transition: background 0.2s ease;
        min-width: 106px;
    }

    .send-button:hover {
        background: #15803d;
        color: #ffffff;
    }

    .empty-chat {
        height: 100%;
        min-height: 300px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #6b7280;
        font-size: 15px;
        text-align: center;
    }

    @media (max-width: 768px) {
        .chat-wrapper {
            margin: 16px auto;
            padding: 0 12px;
        }

        .chat-card {
            padding: 16px;
        }

        .chat-header {
            flex-direction: column;
            align-items: stretch;
        }

        .message-bubble {
            max-width: 100%;
        }

        .message-with-avatar {
            max-width: 92%;
        }

        .message-form {
            flex-direction: column;
            align-items: stretch;
        }

        .send-button {
            width: 100%;
            height: 52px;
        }
    }
</style>
@endsection

@section('main')
<section class="section-5 bg-2 py-5">
    <div class="chat-wrapper">
        <div class="chat-card">
                <div class="chat-header">
                    <div class="chat-header-user">
                        <img src="{{ $otherUser->avatar_url }}"
                             alt="{{ $otherUser->name }} avatar"
                             class="chat-header-avatar"
                             onerror="this.onerror=null; this.src='{{ asset('assets/images/avatar7.png') }}';">
                        <div>
                            <h3 class="chat-title">{{ $otherUser->name }}</h3>
                            <p class="chat-subtitle">Custom ECC ElGamal encrypted conversation</p>
                        </div>
                    </div>
                    <a href="{{ route('messages.index') }}" class="btn btn-outline-secondary">Back</a>
                </div>

                @if(session('success'))
                    <div class="secure-alert alert-dismissible fade show" role="alert">
                        <span>{{ session('success') }}</span>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
                        {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                <div class="messages-box">
                    @if($messages->count() > 0)
                        <div class="messages-list">
                            @foreach($messages as $message)
                                @php($isSent = $message->sender_id === auth()->id())
                                <div class="message-row {{ $isSent ? 'sent' : 'received' }}">
                                    <div class="message-with-avatar">
                                        <img src="{{ $isSent ? auth()->user()->avatar_url : $message->sender->avatar_url }}"
                                             alt="{{ $isSent ? auth()->user()->name : $message->sender->name }} avatar"
                                             class="message-avatar"
                                             onerror="this.onerror=null; this.src='{{ asset('assets/images/avatar7.png') }}';">
                                        <div class="message-bubble">
                                            <div class="message-text">{{ $message->decrypted_body }}</div>
                                            <div class="message-time">{{ $message->created_at->format('M d, Y h:i A') }}</div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="empty-chat">
                            No messages yet. Start the secure conversation.
                        </div>
                    @endif
                </div>

                <form action="{{ route('messages.store', $otherUser->id) }}" method="POST" class="message-form">
                    @csrf
                    <div class="flex-grow-1 w-100">
                        <textarea name="body" class="message-input @error('body') is-invalid @enderror" rows="2" placeholder="Write a secure message">{{ old('body') }}</textarea>
                        @error('body')
                            <p class="invalid-feedback d-block mb-0 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <button type="submit" class="send-button">Send</button>
                </form>
                            </div>
        </div>
    </div>
</section>
@endsection
