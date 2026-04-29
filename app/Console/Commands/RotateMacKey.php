<?php

namespace App\Console\Commands;

use App\Services\Crypto\MACKeyManager;
use Illuminate\Console\Command;

class RotateMacKey extends Command
{
    protected $signature = 'crypto:rotate-mac';
    protected $description = 'Rotate the active custom HMAC key.';

    public function handle(MACKeyManager $keys): int
    {
        $key = $keys->rotate('manual-rotation');
        $this->info('New active MAC key ID: ' . $key->id);
        return self::SUCCESS;
    }
}
