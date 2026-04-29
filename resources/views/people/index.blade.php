@extends('front.layouts.app')

@section('customCss')
<style>
    .people-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
        gap: 14px;
    }

    .people-card {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        padding: 16px;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        background: #ffffff;
        box-shadow: 0 4px 12px rgba(15, 23, 42, 0.05);
        height: 100%;
    }

    .user-info {
        display: flex;
        align-items: center;
        gap: 14px;
        min-width: 0;
    }

    .user-avatar {
        width: 52px;
        height: 52px;
        border-radius: 50%;
        object-fit: cover;
        border: 2px solid #e5e7eb;
        background: #f3f4f6;
        flex: 0 0 auto;
    }

    .user-name {
        font-weight: 700;
        color: #111827;
        margin-bottom: 2px;
    }

    .user-meta {
        font-size: 14px;
        color: #6b7280;
        margin-bottom: 2px;
    }

    .user-summary {
        font-size: 13px;
        color: #6b7280;
        margin-bottom: 0;
    }

    .people-actions {
        flex: 0 0 auto;
        text-align: right;
    }

    @media (max-width: 576px) {
        .people-card {
            align-items: flex-start;
            flex-direction: column;
        }

        .people-actions {
            text-align: left;
            width: 100%;
        }
    }
</style>
@endsection

@section('main')
<section class="section-5 bg-2 py-5">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3 class="fs-4 mb-0">Find People</h3>
            <div>
                <a href="{{ route('connections.index') }}" class="btn btn-outline-primary me-2">My Connections</a>
                <a href="{{ route('connections.pending') }}" class="btn btn-outline-secondary">Connection Requests</a>
            </div>
        </div>

        @include('front.message')

        <form method="GET" action="{{ route('people.index') }}" class="mb-4">
            <div class="input-group">
                <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Search people by name, email, role, or profile">
                <button class="btn btn-primary" type="submit">Search</button>
            </div>
        </form>

        <div class="people-grid">
            @forelse($users as $user)
                @php($state = $states[$user->id] ?? ['status' => 'none'])
                <div class="people-card">
                    <div class="user-info">
                        <img src="{{ $user->avatar_url }}"
                             alt="{{ $user->name }} avatar"
                             class="user-avatar"
                             onerror="this.onerror=null; this.src='{{ asset('assets/images/avatar7.png') }}';">
                        <div>
                            <div class="user-name">{{ $user->name }}</div>
                            <div class="user-meta">{{ ucfirst($user->user_type) }}</div>
                            <p class="user-summary">{{ $user->designation ?: 'No profile summary yet.' }}</p>
                        </div>
                    </div>

                    <div class="people-actions">
                            @if($state['status'] === 'connected')
                                <a href="{{ route('messages.show', $user->id) }}" class="btn btn-success btn-sm">Message</a>
                                <span class="badge bg-success ms-2">Connected</span>
                            @elseif($state['status'] === 'sent')
                                <form action="{{ route('connections.cancel', $state['request']->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-outline-secondary btn-sm">Cancel Request</button>
                                </form>
                                <span class="badge bg-warning text-dark ms-2">Request Sent</span>
                            @elseif($state['status'] === 'received')
                                <form action="{{ route('connections.accept', $state['request']->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-primary btn-sm">Accept</button>
                                </form>
                                <form action="{{ route('connections.reject', $state['request']->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-outline-danger btn-sm">Reject</button>
                                </form>
                            @else
                                <form action="{{ route('connections.request', $user->id) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="btn btn-primary btn-sm">Connect</button>
                                </form>
                            @endif
                    </div>
                </div>
            @empty
                <div class="card border-0 shadow">
                    <div class="card-body text-center text-muted">No users found.</div>
                </div>
            @endforelse
        </div>
    </div>
</section>
@endsection
