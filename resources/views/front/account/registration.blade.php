@extends('front.layouts.app')

@section('main')

<section class="section-5">
    <div class="container my-5">
        <div class="py-lg-2">&nbsp;</div>
        <div class="row d-flex justify-content-center">
            <div class="col-md-5">
                <div class="card shadow border-0 p-5">
                    <h1 class="h3">Register</h1>
                    <form action="" name="registrationForm" id="registrationForm">
                        @csrf <!-- CSRF Token -->
                        <div class="mb-3">
                            <label for="name" class="mb-2">Name*</label>
                            <input type="text" name="name" id="name" class="form-control" placeholder="Enter Name">
                            <p> </p>
                        </div> 
                        <div class="mb-3">
                            <label for="email" class="mb-2">Email*</label>
                            <input type="text" name="email" id="email" class="form-control" placeholder="Enter Email">
                            <p> </p>
                        </div> 
                        <div class="mb-3">
                            <label class="mb-2">Register as*</label>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="user_type" id="aspirant" value="aspirant" checked>
                                <label class="form-check-label" for="aspirant">
                                    Aspirant
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="user_type" id="employer" value="employer">
                                <label class="form-check-label" for="employer">
                                    Employer
                                </label>
                            </div>
                            <p> </p>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="mb-2">Password*</label>
                            <input type="password" name="password" id="password" class="form-control" placeholder="Enter Password">
                            <p> </p>
                        </div> 
                        <div class="mb-3">
                            <label for="confirm_password" class="mb-2">Confirm Password*</label>
                            <input type="password" name="confirm_password" id="confirm_password" class="form-control" placeholder="Confirm Password">
                            <p> </p>
                        </div> 
                        <button class="btn btn-primary mt-2">Register</button>
                    </form>                    
                </div>
                <div class="mt-4 text-center">
                    <p>Have an account? <a href="{{ route('account.login') }}">Login</a></p>
                </div>
                <div class="mt-4 text-center">
                    <a href="{{ url('/') }}" class="btn btn-primary">Home</a>
                </div>
            </div>
        </div>
    </div>
</section>

@endsection

@section('customJs')
<script>
    // CSRF token setup
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    $("#registrationForm").submit(function(e) {
        e.preventDefault();

        $.ajax({
            url: '{{ route("account.processRegistration") }}',
            type: 'post',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.status == true) {
                    $("#registrationForm").find('p.invalid-feedback').remove();
                    $("#registrationForm").find('.is-invalid').removeClass('is-invalid');
                    
                    // Show success message
                    $('.alert-success').remove();
                    $("#registrationForm").before('<div class="alert alert-success">' + response.message + '</div>');
                    
                    // Clear the form
                    $("#registrationForm")[0].reset();
                    
                    // Redirect to the Hirely email verification page after a short delay
                    setTimeout(function() {
                        window.location.href = response.redirect;
                    }, 2000);
                    
                } else {
                    var errors = response.errors;
                    
                    $("#registrationForm").find('p.invalid-feedback').remove();
                    $("#registrationForm").find('.is-invalid').removeClass('is-invalid');
                    
                    $.each(errors, function(key, value) {
                        $('#' + key).addClass('is-invalid')
                            .after('<p class="invalid-feedback">' + value + '</p>');
                    });
                }
            }
        });
    });
</script>
@endsection







