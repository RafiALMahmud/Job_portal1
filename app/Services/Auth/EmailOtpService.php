<?php

namespace App\Services\Auth;

use App\Mail\VerificationCodeMail;
use App\Models\User;
use App\Models\VerificationCode;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class EmailOtpService
{
    public const MAX_ATTEMPTS = 5;
    public const RESEND_COOLDOWN_SECONDS = 60;

    public function generateOtp(): string
    {
        return (string) random_int(100000, 999999);
    }

    public function createOtp(?User $user, string $email, string $purpose, int $expiryMinutes = 5): string
    {
        $emailLookupHash = User::emailLookupHash($email);

        VerificationCode::where('purpose', $purpose)
            ->whereNull('consumed_at')
            ->where(function ($query) use ($user, $emailLookupHash) {
                if ($user) {
                    $query->where('user_id', $user->id);
                } else {
                    $query->where('email_lookup_hash', $emailLookupHash);
                }
            })
            ->update(['consumed_at' => now()]);

        $otp = $this->generateOtp();

        VerificationCode::create([
            'user_id' => $user?->id,
            'email_lookup_hash' => $emailLookupHash,
            'purpose' => $purpose,
            'code_hash' => Hash::make($otp),
            'expires_at' => now()->addMinutes($expiryMinutes),
            'attempts' => 0,
            'resend_available_at' => now()->addSeconds(self::RESEND_COOLDOWN_SECONDS),
        ]);

        return $otp;
    }

    public function createAndSendOtp(?User $user, string $email, string $purpose, int $expiryMinutes = 5): void
    {
        $otp = $this->createOtp($user, $email, $purpose, $expiryMinutes);

        try {
            Mail::to($email)->send(new VerificationCodeMail($otp, $purpose, $expiryMinutes));
        } catch (\Throwable $exception) {
            Log::error('Hirely OTP email failed', [
                'email' => $email,
                'purpose' => $purpose,
                'error' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }

    public function verifyOtp(User|string $userOrEmailHash, string $purpose, string $submittedCode): array
    {
        $code = $this->latestCode($userOrEmailHash, $purpose);

        if (!$code) {
            return ['status' => false, 'message' => 'No active verification code was found. Please request a new code.'];
        }

        if ($code->consumed_at !== null) {
            return ['status' => false, 'message' => 'This verification code has already been used.'];
        }

        if (now()->greaterThan($code->expires_at)) {
            return ['status' => false, 'message' => 'This verification code has expired. Please request a new code.'];
        }

        if ($code->attempts >= self::MAX_ATTEMPTS) {
            return ['status' => false, 'message' => 'Too many incorrect attempts. Please request a new code.'];
        }

        if (!Hash::check($submittedCode, $code->code_hash)) {
            $code->increment('attempts');

            return ['status' => false, 'message' => 'Invalid verification code.'];
        }

        $code->update(['consumed_at' => now()]);

        return ['status' => true, 'message' => 'Verification code accepted.', 'code' => $code];
    }

    public function resendOtp(?User $user, string $email, string $purpose, int $expiryMinutes = 5): array
    {
        $emailLookupHash = User::emailLookupHash($email);
        $latest = VerificationCode::where('purpose', $purpose)
            ->where(function ($query) use ($user, $emailLookupHash) {
                if ($user) {
                    $query->where('user_id', $user->id);
                } else {
                    $query->where('email_lookup_hash', $emailLookupHash);
                }
            })
            ->latest()
            ->first();

        if ($latest && $latest->resend_available_at && now()->lessThan($latest->resend_available_at)) {
            $seconds = max(1, now()->diffInSeconds($latest->resend_available_at));

            return ['status' => false, 'message' => "Please wait {$seconds} seconds before requesting another code."];
        }

        $this->createAndSendOtp($user, $email, $purpose, $expiryMinutes);

        return ['status' => true, 'message' => 'A new Hirely verification code has been sent to your email.'];
    }

    private function latestCode(User|string $userOrEmailHash, string $purpose): ?VerificationCode
    {
        return VerificationCode::where('purpose', $purpose)
            ->whereNull('consumed_at')
            ->where(function ($query) use ($userOrEmailHash) {
                if ($userOrEmailHash instanceof User) {
                    $query->where('user_id', $userOrEmailHash->id);
                } else {
                    $query->where('email_lookup_hash', $userOrEmailHash);
                }
            })
            ->latest()
            ->first();
    }
}
