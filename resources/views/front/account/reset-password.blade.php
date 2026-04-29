@extends('front.layouts.app')

@section('main')
<section class="section-5">
    <div class="container my-5">
        <div class="row d-flex justify-content-center">
            <div class="col-md-5">
                <div class="card shadow border-0 p-5">
                    <h1 class="h3">Reset password</h1>
                    <p class="text-muted">Choose a new password for your Hirely account.</p>

                    @include('front.account.partials.otp-alerts')

                    <form action="{{ route('account.resetPassword') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label for="password" class="mb-2">New password*</label>
                            <input type="password" name="password" id="password" class="form-control @error('password') is-invalid @enderror" placeholder="New password">
                            @error('password')
                                <p class="invalid-feedback">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label for="password_confirmation" class="mb-2">Confirm password*</label>
                            <input type="password" name="password_confirmation" id="password_confirmation" class="form-control @error('password_confirmation') is-invalid @enderror" placeholder="Confirm password">
                            @error('password_confirmation')
                                <p class="invalid-feedback">{{ $message }}</p>
                            @enderror
                        </div>
                        <button class="btn btn-primary w-100">Reset password</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
