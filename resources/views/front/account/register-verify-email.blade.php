@extends('front.layouts.app')

@section('main')
<section class="section-5">
    <div class="container my-5">
        <div class="row d-flex justify-content-center">
            <div class="col-md-5">
                <div class="card shadow border-0 p-5">
                    <h1 class="h3">Verify your Hirely account</h1>
                    <p class="text-muted">Enter the code sent to your email.</p>

                    @include('front.account.partials.otp-alerts')

                    <form action="{{ route('account.verifyRegisterOtp') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label for="otp" class="mb-2">Verification code*</label>
                            <input type="text" name="otp" id="otp" maxlength="6" class="form-control @error('otp') is-invalid @enderror" placeholder="123456" autofocus>
                            @error('otp')
                                <p class="invalid-feedback">{{ $message }}</p>
                            @enderror
                        </div>
                        <button class="btn btn-primary w-100">Verify</button>
                    </form>

                    <form action="{{ route('account.resendRegisterOtp') }}" method="POST" class="mt-3">
                        @csrf
                        <button class="btn btn-outline-primary w-100">Resend code</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
