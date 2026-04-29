@extends('front.layouts.app')

@section('main')
<section class="section-5">
    <div class="container my-5">
        <div class="row d-flex justify-content-center">
            <div class="col-md-5">
                <div class="card shadow border-0 p-5">
                    <h1 class="h3">Forgot password</h1>
                    <p class="text-muted">Enter your Hirely email address and we will send a reset code.</p>

                    @include('front.account.partials.otp-alerts')

                    <form action="{{ route('account.sendForgotPasswordCode') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label for="email" class="mb-2">Email*</label>
                            <input type="email" name="email" id="email" value="{{ old('email') }}" class="form-control @error('email') is-invalid @enderror" placeholder="example@example.com" autofocus>
                            @error('email')
                                <p class="invalid-feedback">{{ $message }}</p>
                            @enderror
                        </div>
                        <button class="btn btn-primary w-100">Send code</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
