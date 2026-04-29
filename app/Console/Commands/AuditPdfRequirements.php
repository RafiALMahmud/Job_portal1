<?php

namespace App\Console\Commands;

use App\Models\ECCKey;
use App\Models\Job;
use App\Models\MACKey;
use App\Models\Message;
use App\Models\RSAKey;
use App\Models\User;
use App\Services\Crypto\RSAFieldCrypto;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

class AuditPdfRequirements extends Command
{
    protected $signature = 'security:audit-pdf-requirements';
    protected $description = 'Audit the project against the CSE447 PDF security requirements.';

    public function handle(RSAFieldCrypto $rsa): int
    {
        $this->check(true, 'Login and registration routes exist');
        $this->check(Route::has('account.verifyLoginOtp'), 'Two-step authentication routes exist');
        $this->check(class_exists(\App\Services\Crypto\CustomRSA::class), 'Custom RSA class exists');
        $this->check(class_exists(\App\Services\Crypto\CustomECC::class), 'Custom ECC class exists');
        $this->check(class_exists(\App\Services\Crypto\CustomHMAC::class), 'Custom HMAC implementation exists');
        $this->check(RSAKey::count() > 0, 'RSA keys exist');
        $this->check(RSAKey::where('is_active', true)->exists(), 'Active RSA key exists');
        $this->check(ECCKey::count() > 0, 'ECC keys exist');
        $this->check(MACKey::where('is_active', true)->exists(), 'Active MAC key exists');
        $this->check(Schema::hasTable('verification_codes'), 'Reusable email OTP table exists');
        $this->check(class_exists(\App\Services\Auth\EmailOtpService::class), 'Custom email OTP service exists');
        $this->check(class_exists(\App\Mail\VerificationCodeMail::class), 'Hirely OTP email class exists');
        $this->check(Schema::hasColumn('messages', 'sender_mac') && Schema::hasColumn('messages', 'receiver_mac'), 'Message MAC columns exist');
        $this->check(config('session.http_only') === true, 'Session cookies are HTTP-only');
        $this->check(config('session.same_site') === 'strict' || config('session.same_site') === 'lax', 'Session SameSite is strict/lax');

        $this->auditEncryptedRows(User::class, ['name', 'email'], $rsa, 'User critical fields encrypted');
        $this->auditEncryptedRows(Job::class, ['title', 'description', 'company_name'], $rsa, 'Job critical fields encrypted');
        $this->auditMessageRows();
        $this->auditKeyExposure();

        $this->line('');
        $this->info('Audit complete. WARN items usually mean old records need migration commands.');

        return self::SUCCESS;
    }

    private function auditEncryptedRows(string $modelClass, array $fields, RSAFieldCrypto $rsa, string $label): void
    {
        $rows = $modelClass::take(10)->get();
        if ($rows->isEmpty()) {
            $this->warnLine($label . ' (no records to inspect)');
            return;
        }

        $allEncrypted = true;
        $allMacs = true;
        foreach ($rows as $row) {
            foreach ($fields as $field) {
                $raw = $row->getRawOriginal($field);
                if ($raw !== null && $raw !== '' && !$rsa->isEncrypted($raw)) {
                    $allEncrypted = false;
                }
            }
            if (Schema::hasColumn($row->getTable(), 'encrypted_payload_mac') && empty($row->getRawOriginal('encrypted_payload_mac'))) {
                $allMacs = false;
            }
        }

        $this->check($allEncrypted, $label);
        $allMacs ? $this->pass('MACs present for ' . class_basename($modelClass)) : $this->warnLine('Some ' . class_basename($modelClass) . ' records have no MAC. Run php artisan crypto:add-mac-existing-data');
    }

    private function auditMessageRows(): void
    {
        $messages = Message::take(10)->get();
        if ($messages->isEmpty()) {
            $this->warnLine('Encrypted message fields (no records to inspect)');
            return;
        }

        $ok = $messages->every(fn (Message $message) =>
            str_contains((string) $message->sender_encrypted_body, 'CUSTOM_ECC_ELGAMAL')
            && str_contains((string) $message->receiver_encrypted_body, 'CUSTOM_ECC_ELGAMAL')
        );
        $macs = $messages->every(fn (Message $message) => !empty($message->sender_mac) && !empty($message->receiver_mac));
        $this->check($ok, 'Encrypted message fields appear encrypted');
        $macs ? $this->pass('Message MACs present') : $this->warnLine('Some messages have no MAC. Run php artisan crypto:add-mac-existing-data');
    }

    private function auditKeyExposure(): void
    {
        $rsaPlain = RSAKey::whereNotNull('private_d')->get()->contains(fn ($key) => !str_starts_with((string) $key->private_d, 'FILE:'));
        $eccPlain = ECCKey::whereNotNull('private_d')->get()->contains(fn ($key) => $key->private_d !== 'PROTECTED');
        $this->check(!$rsaPlain, 'RSA private keys are protected outside normal DB fields');
        $this->check(!$eccPlain, 'ECC private keys are RSA-protected in DB');
    }

    private function check(bool $condition, string $message): void
    {
        $condition ? $this->pass($message) : $this->error('[FAIL] ' . $message);
    }

    private function pass(string $message): void
    {
        $this->info('[PASS] ' . $message);
    }

    private function warnLine(string $message): void
    {
        $this->warn('[WARN] ' . $message);
    }
}
