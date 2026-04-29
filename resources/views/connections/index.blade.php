@extends('front.layouts.app')

@section('customCss')
<style>
    .connection-card {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        padding: 16px;
        border-bottom: 1px solid #e5e7eb;
        background: #ffffff;
    }

    .connection-card:last-child {
        border-bottom: 0;
    }

    .user-info {
        display: flex;
        align-items: center;
        gap: 14px;
    }

    .connection-avatar {
        width: 52px;
        height: 52px;
        border-radius: 50%;
        object-fit: cover;
        border: 2px solid #e5e7eb;
        background: #f3f4f6;
    }

    .user-name {
        font-weight: 700;
        color: #111827;
        margin-bottom: 2px;
    }

    .user-meta {
        font-size: 14px;
        color: #6b7280;
    }
</style>
@endsection

@section('main')
<section class="section-5 bg-2 py-5">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3 class="fs-4 mb-0">My Connections</h3>
            <a href="{{ route('people.index') }}" class="btn btn-outline-primary">Find People</a>
        </div>

        @include('front.message')

        <div class="card border-0 shadow">
            <div class="card-body p-0">
                @forelse($connections as $connection)
                    @php($otherUser = $connection->otherUser(auth()->id()))
                    @if($otherUser)
                        <div class="connection-card">
                            <div class="user-info">
                                <img src="{{ $otherUser->avatar_url }}"
                                     alt="{{ $otherUser->name }} avatar"
                                     class="connection-avatar"
                                     onerror="this.onerror=null; this.src='{{ asset('assets/images/avatar7.png') }}';">
                                <div>
                                    <div class="user-name">{{ $otherUser->name }}</div>
                                    <div class="user-meta">{{ ucfirst($otherUser->user_type) }}</div>
                                </div>
                            </div>
                            <a href="{{ route('messages.show', $otherUser->id) }}" class="btn btn-success btn-sm">Message</a>
                        </div>
                    @endif
                @empty
                    <div class="p-4 text-center text-muted">No accepted connections yet.</div>
                @endforelse
            </div>
        </div>
    </div>
</section>
@endsection
