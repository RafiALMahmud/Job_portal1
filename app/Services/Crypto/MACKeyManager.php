<?php

namespace App\Services\Crypto;

use App\Models\MACKey;
use Illuminate\Support\Facades\DB;

class MACKeyManager
{
    public function __construct(private readonly RSAFieldCrypto $rsaFields)
    {
    }

    public function activeKey(): MACKey
    {
        $key = MACKey::where('is_active', true)->latest('id')->first();

        return $key ?: $this->generate('default');
    }

    public function rawActiveKey(): string
    {
        return $this->decryptKeyValue($this->activeKey());
    }

    public function generate(string $keyName = 'default'): MACKey
    {
        $raw = $this->randomKey();

        // The MAC key is protected with the existing custom RSA field wrapper.
        // This keeps a database dump from exposing the raw MAC key directly.
        return MACKey::create([
            'key_name' => $keyName,
            'key_value' => $this->rsaFields->encryptField($raw),
            'is_active' => true,
        ]);
    }

    public function rotate(string $keyName = 'rotation'): MACKey
    {
        return DB::transaction(function () use ($keyName) {
            MACKey::where('is_active', true)->update([
                'is_active' => false,
                'rotated_at' => now(),
            ]);

            return $this->generate($keyName);
        });
    }

    private function decryptKeyValue(MACKey $key): string
    {
        return (string) $this->rsaFields->decryptField($key->key_value);
    }

    private function randomKey(): string
    {
        $bytes = '';
        for ($i = 0; $i < 32; $i++) {
            $bytes .= chr(random_int(0, 255));
        }

        return bin2hex($bytes);
    }
}
