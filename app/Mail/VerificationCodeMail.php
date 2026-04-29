<?php

namespace App\Mail;

use App\Models\VerificationCode;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class VerificationCodeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $code,
        public string $purpose,
        public int $expiryMinutes
    ) {
    }

    public function build()
    {
        return $this->subject($this->subjectForPurpose())
            ->view('emails.verification-code');
    }

    private function subjectForPurpose(): string
    {
        return match ($this->purpose) {
            VerificationCode::PURPOSE_REGISTER => 'Your Hirely registration verification code',
            VerificationCode::PURPOSE_LOGIN => 'Your Hirely login verification code',
            VerificationCode::PURPOSE_FORGOT_PASSWORD => 'Your Hirely password reset code',
            default => 'Your Hirely verification code',
        };
    }
}
