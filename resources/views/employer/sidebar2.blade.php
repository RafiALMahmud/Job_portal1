<div class="card border-0 shadow mb-4 p-3 mt-4">
    <div class="s-body text-center mt-3">
        @if(Auth::user()->image)
            <img src="{{ asset('profile/' . Auth::user()->image) }}" alt="avatar" class="rounded-circle img-fluid" style="width: 150px;">
        @else
            <img src="assets/assets/images/avatar7.png" alt="avatar" class="rounded-circle img-fluid" style="width: 150px;">
        @endif
        <h5 class="mt-3 pb-0">{{ Auth::user()->name }}</h5>
        <p class="text-muted mb-1 fs-6">Employer</p>
        <div class="d-flex justify-content-center mb-2">
            <button data-bs-toggle="modal" data-bs-target="#exampleModal" type="button" class="btn btn-primary">Change Profile Picture</button>
        </div>
    </div>
</div>
<div class="card account-nav border-0 shadow mb-4 mb-lg-0 mt-3">
    <div class="card-body p-0">
        <ul class="list-group list-group-flush">
            <li class="list-group-item d-flex justify-content-between p-3">
                <a href="{{ route('account.profile') }}" class="text-dark">Account Settings</a>
            </li>
            <li class="list-group-item d-flex justify-content-between align-items-center p-3">
                <a href="{{ route('employer.dashboard') }}" class="text-dark">Dashboard</a>
            </li>
            <li class="list-group-item d-flex justify-content-between align-items-center p-3">
                <a href="{{ route('account.createJob') }}" class="text-dark">Post a Job</a>
            </li>
            <li class="list-group-item d-flex justify-content-between align-items-center p-3">
                <a href="{{ route('account.myJobs') }}" class="text-dark">My Jobs</a>
            </li>
            <li class="list-group-item d-flex justify-content-between align-items-center p-3">
                <a href="{{ route('account.logout') }}" style="font-weight: bold; color: #dc3545;">Logout</a>
            </li>
        </ul>
    </div>
</div>