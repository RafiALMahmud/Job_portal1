<?php

namespace App\Services\Crypto;

use App\Models\RSAKey;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class RSAKeyManager
{
    public function __construct(private readonly CustomRSA $rsa)
    {
    }

    public function activeKey(): RSAKey
    {
        $key = RSAKey::where('is_active', true)->latest('id')->first();

        return $key ?: $this->generateKeyPair('default');
    }

    public function findKey(int $keyId): RSAKey
    {
        return RSAKey::findOrFail($keyId);
    }

    public function generateKeyPair(string $keyName = 'default'): RSAKey
    {
        $material = $this->rsa->generateKeyPair();

        $key = RSAKey::create([
            'key_name' => $keyName,
            'public_n' => $material['public_n'],
            'public_e' => $material['public_e'],
            'private_d' => $material['private_d'],
            'prime_p' => $material['prime_p'],
            'prime_q' => $material['prime_q'],
            'is_active' => true,
        ]);

        $this->protectKeyMaterial($key, $material['private_d']);

        return $key->fresh();
    }

    public function rotate(string $keyName = 'rotation'): RSAKey
    {
        return DB::transaction(function () use ($keyName) {
            RSAKey::where('is_active', true)->update([
                'is_active' => false,
                'rotated_at' => now(),
            ]);

            return $this->generateKeyPair($keyName);
        });
    }

    public function privateExponent(RSAKey $key): string
    {
        if (str_starts_with((string) $key->private_d, 'FILE:')) {
            $path = substr((string) $key->private_d, 5);
            return trim(Storage::disk('local')->get($path));
        }

        return (string) $key->private_d;
    }

    public function protectExistingPlaintextKeys(): int
    {
        $count = 0;
        RSAKey::all()->each(function (RSAKey $key) use (&$count) {
            if (!empty($key->private_d) && !str_starts_with((string) $key->private_d, 'FILE:')) {
                $this->protectKeyMaterial($key, (string) $key->private_d);
                $count++;
            }
        });

        return $count;
    }

    private function protectKeyMaterial(RSAKey $key, string $privateD): void
    {
        $path = 'crypto/rsa_private_' . $key->id . '.key';
        Storage::disk('local')->put($path, $privateD);
        $key->private_d = 'FILE:' . $path;
        $key->prime_p = 'PROTECTED';
        $key->prime_q = 'PROTECTED';
        $key->save();
    }
}
