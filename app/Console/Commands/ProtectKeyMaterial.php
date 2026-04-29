<?php

namespace App\Console\Commands;

use App\Services\Crypto\ECCKeyManager;
use App\Services\Crypto\RSAKeyManager;
use Illuminate\Console\Command;

class ProtectKeyMaterial extends Command
{
    protected $signature = 'crypto:protect-key-material';
    protected $description = 'Move/protect plaintext RSA and ECC private key material.';

    public function handle(RSAKeyManager $rsa, ECCKeyManager $ecc): int
    {
        $rsaCount = $rsa->protectExistingPlaintextKeys();
        $eccCount = $ecc->protectExistingPlaintextKeys();
        $this->info("RSA private keys protected: {$rsaCount}");
        $this->info("ECC private keys protected: {$eccCount}");
        return self::SUCCESS;
    }
}
