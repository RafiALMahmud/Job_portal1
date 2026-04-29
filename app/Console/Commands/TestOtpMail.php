<?php

namespace App\Console\Commands;

use App\Mail\VerificationCodeMail;
use App\Models\VerificationCode;
use App\Services\Auth\EmailOtpService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class TestOtpMail extends Command
{
    protected $signature = 'mail:test-otp {email}';
    protected $description = 'Send a sample Hirely OTP email and print the SMTP result.';

    public function handle(EmailOtpService $emailOtpService): int
    {
        $email = mb_strtolower(trim((string) $this->argument('email')));
        $otp = $emailOtpService->generateOtp();

        $this->line('Sending Hirely test OTP email...');
        $this->line('To: ' . $email);
        $this->line('Generated OTP: ' . $otp);

        try {
            Mail::to($email)->send(new VerificationCodeMail($otp, VerificationCode::PURPOSE_LOGIN, 5));
        } catch (\Throwable $exception) {
            Log::error('Hirely test OTP email failed', [
                'email' => $email,
                'error' => $exception->getMessage(),
            ]);

            $this->error('Failed to send Hirely OTP email.');
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info('Hirely OTP email sent successfully.');

        return self::SUCCESS;
    }
}
