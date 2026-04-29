<?php

namespace App\Services\Crypto;

use App\Models\ECCKey;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ECCKeyManager
{
    public function __construct(
        private readonly CustomECC $ecc,
        private readonly RSAFieldCrypto $rsaFields
    )
    {
    }

    public function activeKeyForUser(User|int $user): ECCKey
    {
        $userId = $user instanceof User ? $user->id : $user;
        $key = ECCKey::where('user_id', $userId)->where('is_active', true)->latest('id')->first();

        return $key ?: $this->generateForUser($userId);
    }

    public function generateForUser(User|int $user): ECCKey
    {
        $userId = $user instanceof User ? $user->id : $user;
        $material = $this->ecc->generateKeyPair();

        return ECCKey::create([
            'user_id' => $userId,
            'public_x' => $material['public_x'],
            'public_y' => $material['public_y'],
            'private_d' => 'PROTECTED',
            'private_d_encrypted' => $this->rsaFields->encryptField($material['private_d']),
            'curve_name' => $material['curve_name'],
            'is_active' => true,
        ]);
    }

    public function findKey(int $keyId): ECCKey
    {
        return ECCKey::findOrFail($keyId);
    }

    public function rotateForUser(User|int $user): ECCKey
    {
        $userId = $user instanceof User ? $user->id : $user;

        return DB::transaction(function () use ($userId) {
            ECCKey::where('user_id', $userId)->where('is_active', true)->update([
                'is_active' => false,
                'rotated_at' => now(),
            ]);

            return $this->generateForUser($userId);
        });
    }

    public function publicPoint(ECCKey $key): array
    {
        return ['x' => (int) $key->public_x, 'y' => (int) $key->public_y];
    }

    public function privateScalar(ECCKey $key): string
    {
        if (!empty($key->private_d_encrypted)) {
            return (string) $this->rsaFields->decryptField($key->private_d_encrypted);
        }

        return (string) $key->private_d;
    }

    public function protectExistingPlaintextKeys(): int
    {
        $count = 0;
        ECCKey::whereNull('private_d_encrypted')->get()->each(function (ECCKey $key) use (&$count) {
            if (!empty($key->private_d) && $key->private_d !== 'PROTECTED') {
                $key->private_d_encrypted = $this->rsaFields->encryptField((string) $key->private_d);
                $key->private_d = 'PROTECTED';
                $key->save();
                $count++;
            }
        });

        return $count;
    }
}
