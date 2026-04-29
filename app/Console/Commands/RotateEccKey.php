<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\Crypto\ECCKeyManager;
use Illuminate\Console\Command;

class RotateEccKey extends Command
{
    protected $signature = 'crypto:rotate-ecc {user_id}';
    protected $description = 'Rotate the active custom ECC key for a user.';

    public function handle(ECCKeyManager $keys): int
    {
        $user = User::findOrFail((int) $this->argument('user_id'));
        $key = $keys->rotateForUser($user);
        $this->info("New active ECC key for user {$user->id}: {$key->id}");
        return self::SUCCESS;
    }
}
