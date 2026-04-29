<?php

namespace App\Services\Crypto;

class RecordMac
{
    public function __construct(
        private readonly CustomHMAC $hmac,
        private readonly MACKeyManager $keys
    ) {
    }

    public function sign(string $payload): string
    {
        return $this->hmac->sign($payload, $this->keys->rawActiveKey());
    }

    public function verify(string $payload, ?string $mac): bool
    {
        return $this->hmac->verify($payload, $this->keys->rawActiveKey(), $mac);
    }
}
