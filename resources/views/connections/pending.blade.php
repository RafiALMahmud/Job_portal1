@extends('front.layouts.app')

@section('customCss')
<style>
    .request-card {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        padding: 16px;
        border-bottom: 1px solid #e5e7eb;
        background: #ffffff;
    }

    .request-card:last-child {
        border-bottom: 0;
    }

    .user-info {
        display: flex;
        align-items: center;
        gap: 14px;
    }

    .request-avatar {
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

    @media (max-width: 576px) {
        .request-card {
            align-items: flex-start;
            flex-direction: column;
        }
    }
</style>
@endsection

@section('main')
<section class="section-5 bg-2 py-5">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3 class="fs-4 mb-0">Connection Requests</h3>
            <a href="{{ route('people.index') }}" class="btn btn-outline-primary">Find People</a>
        </div>

        @include('front.message')

        <div class="card border-0 shadow">
            <div class="card-body p-0">
                @forelse($requests as $request)
                    <div class="request-card">
                        <div class="user-info">
                            <img src="{{ $request->sender->avatar_url }}"
                                 alt="{{ $request->sender->name }} avatar"
                                 class="request-avatar"
                                 onerror="this.onerror=null; this.src='{{ asset('assets/images/avatar7.png') }}';">
                            <div>
                                <div class="user-name">{{ $request->sender->name }}</div>
                                <div class="user-meta">{{ ucfirst($request->sender->user_type) }}</div>
                            </div>
                        </div>
                        <div>
                            <form action="{{ route('connections.accept', $request->id) }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-primary btn-sm">Accept</button>
                            </form>
                            <form action="{{ route('connections.reject', $request->id) }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-outline-danger btn-sm">Reject</button>
                            </form>
                        </div>
                    </div>
                @empty
                    <div class="p-4 text-center text-muted">No pending connection requests.</div>
                @endforelse
            </div>
        </div>
    </div>
</section>
@endsection
