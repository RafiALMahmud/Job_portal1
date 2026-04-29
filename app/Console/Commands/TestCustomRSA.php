<?php

namespace App\Console\Commands;

use App\Services\Crypto\RSAFieldCrypto;
use App\Services\Crypto\RSAKeyManager;
use Illuminate\Console\Command;

class TestCustomRSA extends Command
{
    protected $signature = 'crypto:test-rsa {text=Custom RSA lab test message}';

    protected $description = 'Encrypt and decrypt sample text with the custom RSA implementation.';

    public function handle(RSAKeyManager $keys, RSAFieldCrypto $crypto): int
    {
        $key = $keys->activeKey();
        $original = (string) $this->argument('text');
        $encrypted = $crypto->encryptField($original);
        $payload = json_decode((string) $encrypted, true);
        $decrypted = $crypto->decryptField($encrypted);

        $this->line('Original: ' . $original);
        $this->line('Key ID: ' . $key->id);
        $this->line('Encrypted blocks: ' . implode(', ', $payload['blocks'] ?? []));
        $this->line('Decrypted: ' . $decrypted);
        $this->line('Match: ' . ($original === $decrypted ? 'YES' : 'NO'));

        return $original === $decrypted ? self::SUCCESS : self::FAILURE;
    }
}
