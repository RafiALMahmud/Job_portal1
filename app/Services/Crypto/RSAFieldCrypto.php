<?php

namespace App\Services\Crypto;

class RSAFieldCrypto
{
    public function __construct(
        private readonly CustomRSA $rsa,
        private readonly RSAKeyManager $keys
    ) {
    }

    public function encryptField(?string $plaintext): ?string
    {
        if ($plaintext === null || $plaintext === '') {
            return $plaintext;
        }

        if ($this->isEncrypted($plaintext)) {
            return $plaintext;
        }

        $key = $this->keys->activeKey();

        return json_encode([
            'algorithm' => 'CUSTOM_RSA',
            'key_id' => $key->id,
            'blocks' => $this->rsa->encrypt($plaintext, $key->public_n, $key->public_e),
            'created_at' => now()->toIso8601String(),
            'version' => 1,
        ]);
    }

    public function decryptField(?string $ciphertextJson): ?string
    {
        if ($ciphertextJson === null || $ciphertextJson === '') {
            return $ciphertextJson;
        }

        $payload = json_decode($ciphertextJson, true);
        if (!$this->validPayload($payload)) {
            return $ciphertextJson;
        }

        $key = $this->keys->findKey((int) $payload['key_id']);

        return $this->rsa->decrypt($payload['blocks'], $key->public_n, $this->keys->privateExponent($key));
    }

    public function isEncrypted(?string $value): bool
    {
        if ($value === null || $value === '') {
            return false;
        }

        return $this->validPayload(json_decode($value, true));
    }

    private function validPayload(mixed $payload): bool
    {
        return is_array($payload)
            && ($payload['algorithm'] ?? null) === 'CUSTOM_RSA'
            && isset($payload['key_id'])
            && isset($payload['blocks'])
            && is_array($payload['blocks']);
    }
}
