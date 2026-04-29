@extends('front.layouts.app')

@section('customCss')
<style>
    .messenger-shell {
        max-width: 1180px;
        margin: 0 auto;
        padding: 0 18px;
    }

    .messenger-card {
        display: grid;
        grid-template-columns: minmax(0, 1fr) 360px;
        min-height: 620px;
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 16px;
        box-shadow: 0 10px 28px rgba(15, 23, 42, 0.08);
        overflow: hidden;
    }

    .messenger-main,
    .messenger-sidebar {
        padding: 24px;
    }

    .messenger-main {
        border-right: 1px solid #e5e7eb;
    }

    .messenger-title {
        font-size: 28px;
        font-weight: 800;
        color: #111827;
        margin-bottom: 6px;
    }

    .messenger-subtitle {
        color: #6b7280;
        margin-bottom: 22px;
    }

    .section-heading {
        font-size: 15px;
        font-weight: 700;
        color: #374151;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        margin-bottom: 12px;
    }

    .conversation-list,
    .connection-list {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .conversation-row,
    .connection-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 14px;
        padding: 14px;
        border: 1px solid #e5e7eb;
        border-radius: 14px;
        color: #111827;
        text-decoration: none;
        background: #ffffff;
        transition: background 0.18s ease, border-color 0.18s ease, transform 0.18s ease;
    }

    .conversation-row:hover,
    .connection-row:hover {
        background: #f8fafc;
        border-color: #bfdbfe;
        color: #111827;
        transform: translateY(-1px);
    }

    .conversation-user,
    .connection-user {
        display: flex;
        align-items: center;
        gap: 12px;
        min-width: 0;
    }

    .messenger-avatar {
        width: 52px;
        height: 52px;
        border-radius: 50%;
        object-fit: cover;
        border: 2px solid #e5e7eb;
        background: #f3f4f6;
        flex: 0 0 auto;
    }

    .conversation-name,
    .connection-name {
        font-weight: 700;
        color: #111827;
        margin-bottom: 2px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .conversation-meta,
    .connection-meta {
        color: #6b7280;
        font-size: 14px;
    }

    .unread-badge {
        min-width: 26px;
        height: 26px;
        border-radius: 999px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: #2563eb;
        color: #ffffff;
        font-size: 13px;
        font-weight: 700;
    }

    .connection-action {
        color: #2563eb;
        font-weight: 700;
        font-size: 14px;
        flex: 0 0 auto;
    }

    .empty-panel {
        min-height: 240px;
        border: 1px dashed #cbd5e1;
        border-radius: 14px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-align: center;
        color: #6b7280;
        padding: 24px;
        background: #f8fafc;
    }

    @media (max-width: 992px) {
        .messenger-card {
            grid-template-columns: 1fr;
        }

        .messenger-main {
            border-right: 0;
            border-bottom: 1px solid #e5e7eb;
        }
    }
</style>
@endsection

@section('main')
<section class="section-5 bg-2 py-5">
    <div class="messenger-shell">
        <div class="messenger-card">
            <div class="messenger-main">
                <h3 class="messenger-title">Messages</h3>
                <p class="messenger-subtitle">Encrypted chats with your accepted connections.</p>
                <div class="section-heading">Recent Conversations</div>

                        @forelse($conversations as $conversation)
                            @php
                                $otherUser = $conversation->otherUser(auth()->id());
                                $lastMessage = $conversation->messages->first();
                                $unreadCount = $conversation->messages()
                                    ->where('receiver_id', auth()->id())
                                    ->where('is_read', false)
                                    ->count();
                            @endphp
                            @if($otherUser)
                        <a href="{{ route('messages.show', $otherUser->id) }}" class="conversation-row">
                            <div class="conversation-user">
                                <img src="{{ $otherUser->avatar_url }}"
                                     alt="{{ $otherUser->name }} avatar"
                                     class="messenger-avatar"
                                     onerror="this.onerror=null; this.src='{{ asset('assets/images/avatar7.png') }}';">
                                <div>
                                    <div class="conversation-name">{{ $otherUser->name }}</div>
                                    <div class="conversation-meta">
                                        {{ $lastMessage ? $lastMessage->created_at->diffForHumans() : 'No messages yet' }}
                                    </div>
                                </div>
                            </div>
                            @if($unreadCount > 0)
                                <span class="unread-badge">{{ $unreadCount }}</span>
                            @endif
                        </a>
                            @endif
                        @empty
                    <div class="empty-panel">
                        <div class="fw-semibold mb-2">No conversations yet.</div>
                        <div>Select a connection from the side panel to start a secure chat.</div>
                    </div>
                        @endforelse
            </div>

            <aside class="messenger-sidebar">
                <div class="section-heading">Connections</div>
                        @forelse($users as $user)
                    <a href="{{ route('messages.show', $user->id) }}" class="connection-row">
                        <div class="connection-user">
                            <img src="{{ $user->avatar_url }}"
                                 alt="{{ $user->name }} avatar"
                                 class="messenger-avatar"
                                 onerror="this.onerror=null; this.src='{{ asset('assets/images/avatar7.png') }}';">
                            <div>
                                <div class="connection-name">{{ $user->name }}</div>
                                <div class="connection-meta">{{ ucfirst($user->user_type) }}</div>
                            </div>
                        </div>
                        <span class="connection-action">Chat</span>
                            </a>
                        @empty
                    <div class="empty-panel">
                        <div class="mb-3">Connect with people before messaging them.</div>
                        <a href="{{ route('people.index') }}" class="btn btn-primary btn-sm">Find People</a>
                    </div>
                        @endforelse
            </aside>
        </div>
    </div>
</section>
@endsection
