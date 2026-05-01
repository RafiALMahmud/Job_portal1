@if(Session::has('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ Session::get('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if(Session::has('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        {{ Session::get('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if(Session::has('issued_session_token'))
    <div class="alert alert-info alert-dismissible fade show" role="alert">
        <strong>Secure session token</strong>
        <p class="mb-2">This token is shown only once after login. The browser is now using it for secure authenticated requests.</p>
        <code class="d-block text-break">{{ Session::get('issued_session_token') }}</code>
        @if(Session::has('issued_session_expires_at'))
            <p class="mb-0 mt-2"><small>Expires at: {{ Session::get('issued_session_expires_at') }}</small></p>
        @endif
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif
