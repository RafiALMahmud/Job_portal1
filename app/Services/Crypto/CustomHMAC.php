<?php

namespace App\Services\Crypto;

class CustomHMAC
{
    private const BLOCK_SIZE = 64;

    public function sign(string $message, string $key): string
    {
        $key = $this->normalizeKey($key);
        $ipad = str_repeat(chr(0x36), self::BLOCK_SIZE);
        $opad = str_repeat(chr(0x5c), self::BLOCK_SIZE);
        $innerPad = $key ^ $ipad;
        $outerPad = $key ^ $opad;

        // Manual HMAC formula: H((K xor opad) || H((K xor ipad) || message)).
        // We use hash('sha256') as the hash primitive and implement the wrapper here.
        $innerHash = hex2bin(hash('sha256', $innerPad . $message));

        return hash('sha256', $outerPad . $innerHash);
    }

    public function verify(string $message, string $key, ?string $expected): bool
    {
        if ($expected === null || $expected === '') {
            return false;
        }

        return $this->constantTimeEquals($this->sign($message, $key), $expected);
    }

    private function normalizeKey(string $key): string
    {
        if (strlen($key) > self::BLOCK_SIZE) {
            $key = hex2bin(hash('sha256', $key));
        }

        return str_pad($key, self::BLOCK_SIZE, chr(0x00));
    }

    private function constantTimeEquals(string $known, string $given): bool
    {
        if (function_exists('hash_equals')) {
            return hash_equals($known, $given);
        }

        if (strlen($known) !== strlen($given)) {
            return false;
        }

        $diff = 0;
        for ($i = 0; $i < strlen($known); $i++) {
            $diff |= ord($known[$i]) ^ ord($given[$i]);
        }

        return $diff === 0;
    }
}
