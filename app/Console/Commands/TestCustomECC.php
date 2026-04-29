<?php

namespace App\Console\Commands;

use App\Services\Crypto\CustomECC;
use Illuminate\Console\Command;

class TestCustomECC extends Command
{
    protected $signature = 'crypto:test-ecc {text=Custom ECC lab message}';

    protected $description = 'Encrypt and decrypt sample text with the custom ECC ElGamal implementation.';

    public function handle(CustomECC $ecc): int
    {
        $original = (string) $this->argument('text');
        $keyPair = $ecc->generateKeyPair();
        $publicKey = ['x' => (int) $keyPair['public_x'], 'y' => (int) $keyPair['public_y']];

        $encrypted = $ecc->encrypt($original, $publicKey);
        $decrypted = $ecc->decrypt($encrypted, $keyPair['private_d']);

        $this->line('Original: ' . $original);
        $this->line('Curve: ' . $encrypted['curve']);
        foreach ($encrypted['blocks'] as $index => $block) {
            $this->line('Block ' . ($index + 1) . ' C1: (' . $block['c1']['x'] . ', ' . $block['c1']['y'] . ')');
            $this->line('Block ' . ($index + 1) . ' C2: (' . $block['c2']['x'] . ', ' . $block['c2']['y'] . ')');
        }
        $this->line('Decrypted: ' . $decrypted);
        $this->line('Match: ' . ($original === $decrypted ? 'YES' : 'NO'));

        return $original === $decrypted ? self::SUCCESS : self::FAILURE;
    }
}
