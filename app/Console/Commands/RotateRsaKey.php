<?php

namespace App\Console\Commands;

use App\Services\Crypto\RSAKeyManager;
use Illuminate\Console\Command;

class RotateRsaKey extends Command
{
    protected $signature = 'crypto:rotate-rsa';
    protected $description = 'Rotate the active custom RSA key.';

    public function handle(RSAKeyManager $keys): int
    {
        $key = $keys->rotate('manual-rotation');
        $this->info('New active RSA key ID: ' . $key->id);
        return self::SUCCESS;
    }
}
