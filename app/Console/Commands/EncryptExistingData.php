<?php

namespace App\Console\Commands;

use App\Models\Applicant;
use App\Models\Employer;
use App\Models\Job;
use App\Models\Notification;
use App\Models\User;
use App\Services\Crypto\RSAFieldCrypto;
use App\Services\Crypto\RSAKeyManager;
use Illuminate\Console\Command;

class EncryptExistingData extends Command
{
    protected $signature = 'crypto:encrypt-existing-data';

    protected $description = 'Encrypt existing plaintext critical user, job, profile, application, and notification data.';

    public function handle(RSAKeyManager $keys, RSAFieldCrypto $crypto): int
    {
        $keys->activeKey();

        $this->encryptUsers($crypto);
        $this->encryptModelFields(Job::class, (new Job())->rsaEncryptedFieldsForCommand(), $crypto, 'jobs');
        $this->encryptModelFields(Employer::class, (new Employer())->rsaEncryptedFieldsForCommand(), $crypto, 'employers');
        $this->encryptModelFields(Applicant::class, (new Applicant())->rsaEncryptedFieldsForCommand(), $crypto, 'applicants');
        $this->encryptModelFields(Notification::class, (new Notification())->rsaEncryptedFieldsForCommand(), $crypto, 'notifications');

        $this->info('Existing critical data encryption pass completed.');

        return self::SUCCESS;
    }

    private function encryptUsers(RSAFieldCrypto $crypto): void
    {
        $count = 0;

        User::query()->chunkById(50, function ($users) use (&$count, $crypto) {
            foreach ($users as $user) {
                foreach (['name', 'email', 'mobile', 'designation'] as $field) {
                    $raw = $user->getRawOriginal($field);
                    if ($raw !== null && $raw !== '' && !$crypto->isEncrypted($raw)) {
                        $user->{$field} = $raw;
                    }
                }

                if ($user->email) {
                    $user->email_lookup_hash = User::emailLookupHash($user->email);
                }

                $user->save();
                $count++;
            }
        });

        $this->line("Users checked: {$count}");
    }

    private function encryptModelFields(string $modelClass, array $fields, RSAFieldCrypto $crypto, string $label): void
    {
        $count = 0;

        $modelClass::query()->get()->each(function ($record) use (&$count, $fields, $crypto) {
            foreach ($fields as $field) {
                $raw = $record->getRawOriginal($field);
                if ($raw !== null && $raw !== '' && !$crypto->isEncrypted($raw)) {
                    $record->{$field} = $raw;
                }
            }

            $record->save();
            $count++;
        });

        $this->line(ucfirst($label) . " checked: {$count}");
    }
}
