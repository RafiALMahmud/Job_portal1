<!DOCTYPE html>
<html class="no-js" lang="en_AU" />
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<title>Hirely | Find Best Jobs</title>
	<meta name="description" content="" />
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no, maximum-scale=1, user-scalable=no" />
	<meta name="HandheldFriendly" content="True" />
	<meta name="pinterest" content="nopin" />
	<meta name="csrf-token" content="{{ csrf_token() }}">  {{-- ADDED TO DEAL WWITH CSRF  --}}
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/style.css') }}" />
    {{-- the above line sets the path for css --}}
    @yield('customCss')
	<!-- Fav Icon -->
	<link rel="shortcut icon" type="image/x-icon" href="#" />
</head>
<body data-instant-intensity="mousedown">
<header>
	<nav class="navbar navbar-expand-lg navbar-light bg-white shadow py-3">
		<div class="container">
			<a class="navbar-brand" href="index.html">Hirely</a>
			<button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
				<span class="navbar-toggler-icon"></span>
			</button>
			<div class="collapse navbar-collapse" id="navbarSupportedContent">
				<ul class="navbar-nav ms-0 ms-sm-0 me-auto mb-2 mb-lg-0 ms-lg-4">
					<li class="nav-item">
						<a class="nav-link" aria-current="page" href="{{ route('home') }}">Home</a>
					</li>										
					@if(Auth::check())
					<li class="nav-item">
						<a class="nav-link" href="{{ route('people.index') }}">Find People</a>
					</li>
					<li class="nav-item">
						<a class="nav-link" href="{{ route('messages.index') }}">Messages</a>
					</li>
					<li class="nav-item">
						<a class="nav-link" href="{{ route('connections.index') }}">My Connections</a>
					</li>
					<li class="nav-item">
						<a class="nav-link" href="{{ route('connections.pending') }}">Connection Requests</a>
					</li>
					@endif
				</ul>				

				@if (!Auth::check())
				  <a class="btn btn-outline-primary me-2" href={{ route('account.login') }} type="submit">Login</a>
                @else
				  <a class="btn btn-outline-primary me-2" href={{ route('account.profile') }} type="submit">Account</a>
				  
				  {{-- Add Admin Button if user_type is admin --}}
				  @if(auth()->user()->user_type === 'admin')
					<a href="{{ route('admin.dashboard') }}" class="btn btn-warning me-2">Admin</a>
				  @endif

				  {{-- Add Employer Button if user_type is employer --}}
				  @if(auth()->user()->user_type === 'employer')
					<a href="{{ route('employer.dashboard') }}" class="btn btn-info me-2">Employer</a>
				  @endif

                  {{-- Add notification bell for aspirant --}}
                  @if(auth()->user()->user_type === 'aspirant')
                    <div class="dropdown d-inline me-2">
                        <button class="btn btn-link position-relative" type="button" id="notificationDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-bell fa-lg"></i>
                            @if(isset($unreadNotifications) && $unreadNotifications > 0)
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                    {{ $unreadNotifications }}
                                </span>
                            @endif
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="notificationDropdown" style="width: 300px;">
                            <li class="dropdown-header d-flex justify-content-between align-items-center">
                                <span>Notifications</span>
                                @if(isset($unreadNotifications) && $unreadNotifications > 0)
                                    <a href="#" id="markAllAsReadAspirant" class="text-decoration-none">
                                        Mark all as read
                                    </a>
                                @endif
                            </li>
                            @if(isset($notifications) && count($notifications) > 0)
                                @foreach($notifications as $notification)
                                    <li>
                                        <a class="dropdown-item {{ $notification->is_read ? '' : 'bg-light' }}" 
                                           href="#"
                                           data-notification-id="{{ $notification->id }}">
                                            <div class="d-flex align-items-center">
                                                <div class="flex-grow-1">
                                                    <p class="mb-0">{{ $notification->message }}</p>
                                                    <small class="text-muted">{{ $notification->created_at->diffForHumans() }}</small>
                                                </div>
                                            </div>
                                        </a>
                                    </li>
                                @endforeach
                            @else
                                <li><a class="dropdown-item text-center" href="#">No notifications</a></li>
                            @endif
                        </ul>
                    </div>
                  @endif

                  {{-- Add Apply for Jobs Button if user_type is aspirant --}}
                  @if(auth()->user()->user_type === 'aspirant')
                    <a href="{{ route('jobs') }}" class="btn btn-success me-2">Apply for Jobs</a>
                  @endif
                @endif

                @if (Auth::check() && in_array(Auth::user()->user_type, ['admin', 'employer']))
                    <a class="btn btn-primary" href="{{ route('account.createJob') }}" type="submit">Post a Job</a>
                @else
                    <a class="btn btn-primary" href="{{ route('account.profile') }}" type="submit" onclick="showUnauthorizedMessage()">Post a Job</a>
                @endif
			</div>
		</div>
	</nav>
</header>
@yield('main')
<div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title pb-0" id="exampleModalLabel">Change Profile Picture</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="profilePictureForm" enctype="multipart/form-data">
            @csrf
            <div class="mb-3">
                <label for="image" class="form-label">Profile Image</label>
                <input type="file" class="form-control" id="image" name="image">
                <p class="text-danger" id="image-error"></p>
            </div>
            <div class="d-flex justify-content-end">
                <button type="submit" class="btn btn-primary mx-3">Update</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </form>
      </div>
    </div>
  </div>
</div>

<footer class="bg-dark py-3 bg-2">
<div class="container">
    <p class="text-center text-white pt-3 fw-bold fs-6">© 2025 xyz company, all right reserved</p>
</div>
</footer> 
<script src="{{ asset('assets/js/jquery-3.6.0.min.js') }}"></script>
<script src="{{ asset('assets/js/bootstrap.bundle.5.1.3.min.js') }}"></script>
<script src="{{ asset('assets/js/instantpages.5.1.0.min.js') }}"></script>
<script src="{{ asset('assets/js/instantpages.5.1.0.min.js') }}"></script>
{{-- the above four lines are directed from asset --}}
<script src="{{ asset('assets/js/custom.js') }}"></script>

<script>
function showUnauthorizedMessage() {
    event.preventDefault();
    alert('Only employers and administrators can post jobs. Please contact the administrator if you need this access.');
}

// Profile Picture Upload
$("#profilePictureForm").submit(function(e) {
    e.preventDefault();
    var formData = new FormData(this);
    $.ajax({
        url: '{{ route("account.updateProfilePicture") }}',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            if (response.status == true) {
                window.location.reload();
            } else {
                var errors = response.errors;
                if (errors.image) {
                    $("#image-error").html(errors.image[0]);
                }
            }
        }
    });
});

// Aspirant notification: mark as read
$(document).on('click', '.dropdown-item[data-notification-id]', function(e) {
    var notificationId = $(this).data('notification-id');
    if (notificationId) {
        $.ajax({
            url: '/account/notifications/' + notificationId + '/mark-as-read',
            type: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function() {
                // Optionally update UI
            }
        });
        $(this).removeClass('bg-light');
    }
});

// Aspirant notification: mark all as read
$(document).on('click', '#markAllAsReadAspirant', function(e) {
    e.preventDefault();
    $.ajax({
        url: '/account/notifications/mark-all-as-read',
        type: 'POST',
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function() {
            $('.dropdown-item[data-notification-id]').removeClass('bg-light');
            $('.badge.bg-danger').remove();
        }
    });
});
</script>

@yield('customJs')
</body>
</html>
