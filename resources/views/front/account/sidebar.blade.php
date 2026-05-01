<div class="card border-0 shadow mb-4 p-3">
    <div class="s-body text-center mt-3">
        @if(Auth::user()->image)
            <img src="{{ Storage::url('profile/' . Auth::user()->image) }}" alt="avatar" class="rounded-circle img-fluid" style="width: 150px;">
        @else
            <img src="{{ asset('assets/images/avatar7.png') }}" alt="avatar" class="rounded-circle img-fluid" style="width: 150px;">
        @endif
        <h5 class="mt-3 pb-0">{{ Auth::user()->name }}</h5>
        <p class="text-muted mb-1 fs-6">{{ Auth::user()->designation }}</p>
        <button data-bs-toggle="modal" data-bs-target="#exampleModal" type="button" class="btn btn-sm btn-primary mt-2">
            Upload Image
        </button>
    </div>
</div>
<div class="card account-nav border-0 shadow mb-4 mb-lg-0">
    <div class="card-body p-0">
        <ul class="list-group list-group-flush">
            <li class="list-group-item d-flex justify-content-between p-3">
                <a href="{{ route('account.profile') }}">Account Settings</a>
            </li>
            @if (Auth::check() && in_array(Auth::user()->user_type, ['admin', 'employer']))
                <li class="list-group-item d-flex justify-content-between align-items-center p-3">
                    <a href="{{ route('account.createJob') }}">Post a Job</a>
                </li>
            @endif
            @if (Auth::check() && in_array(Auth::user()->user_type, ['admin', 'employer']))
                <li class="list-group-item d-flex justify-content-between align-items-center p-3">
                    <a href="{{ route('account.myJobs') }}">My Jobs</a>
                </li>
            @endif
            @if (Auth::check() && Auth::user()->user_type == 'aspirant')
            <li class="list-group-item d-flex justify-content-between align-items-center p-3">
                <a href="{{ route('account.jobsApplied') }}">Jobs Applied</a>
            </li>
            <li class="list-group-item d-flex justify-content-between align-items-center p-3">
                <a href="{{ route('account.savedJobs') }}">Saved Jobs</a>
            </li>
            @endif
            <li class="list-group-item d-flex justify-content-between align-items-center p-3">
                <form action="{{ route('account.logout') }}" method="POST" class="w-100">
                    @csrf
                    <button type="submit" class="btn btn-link p-0 text-decoration-none" style="font-weight: bold; color: #dc3545;">Logout</button>
                </form>
            </li>
            <li class="list-group-item d-flex justify-content-between align-items-center p-3">
                <form action="{{ route('account.logoutAllDevices') }}" method="POST" class="w-100">
                    @csrf
                    <button type="submit" class="btn btn-link p-0 text-decoration-none text-warning">Logout All Devices</button>
                </form>
            </li>
        </ul>
    </div>
</div>
