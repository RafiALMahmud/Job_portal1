<div class="card account-nav border-0 shadow mb-4 mb-lg-0">
    <div class="card-body p-0">
        <ul class="list-group list-group-flush ">
            <li class="list-group-item d-flex justify-content-between p-3">
                <a href="{{ route('admin.manage_users') }}">Users</a>
            </li>
            @if (Auth::check() && in_array(Auth::user()->user_type, ['admin', 'employer']))
            <li class="list-group-item d-flex justify-content-between align-items-center p-3">
                <a href="{{ route('account.myJobs') }}">My Jobs</a>
            </li>
            @endif
            <li class="list-group-item d-flex justify-content-between align-items-center p-3">
                <a href="{{ route('admin.job_applications') }}">Job applications</a>
            </li>
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
