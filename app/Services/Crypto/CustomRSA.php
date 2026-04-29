<?php

namespace App\Services\Crypto;

use RuntimeException;

class CustomRSA
{
    private const DEFAULT_E = 65537;
    private const MIN_PRIME = 1000003;
    private const MAX_PRIME = 4000000;

    public function generateKeyPair(): array
    {
        // p and q are selected as different random prime numbers. In a real system
        // these would be hundreds of digits; this lab implementation keeps them
        // within PHP integer limits so the RSA math is visible and self-contained.
        do {
            $p = $this->generatePrime();
            $q = $this->generatePrime();
        } while ($p === $q);

        // n is the public modulus. Every plaintext block must be smaller than n.
        $n = $p * $q;

        // phi(n) is Euler's totient for two primes: (p - 1) * (q - 1).
        $phi = ($p - 1) * ($q - 1);

        // e is the public exponent. It must be coprime with phi(n).
        $e = self::DEFAULT_E;
        if ($this->gcd($e, $phi) !== 1) {
            $e = 3;
            while ($e < $phi && $this->gcd($e, $phi) !== 1) {
                $e += 2;
            }
        }

        if ($e >= $phi) {
            throw new RuntimeException('Unable to find a valid public exponent.');
        }

        // d is the private exponent: the modular inverse of e modulo phi(n).
        $d = $this->modInverse($e, $phi);

        return [
            'public_n' => (string) $n,
            'public_e' => (string) $e,
            'private_d' => (string) $d,
            'prime_p' => (string) $p,
            'prime_q' => (string) $q,
        ];
    }

    public function encrypt(string $plaintext, int|string $n, int|string $e): array
    {
        $n = (int) $n;
        $e = (int) $e;
        $blockSize = $this->blockSize($n);
        $blocks = [];

        // Chunking is needed because RSA encrypts numbers smaller than n, while
        // application text may be much longer than one numeric block.
        foreach (str_split($plaintext, $blockSize) as $chunk) {
            $m = $this->textToNumber($chunk);
            if ($m >= $n) {
                throw new RuntimeException('Plaintext block is too large for the RSA modulus.');
            }

            // Encryption applies the RSA formula directly: c = m^e mod n.
            $blocks[] = (string) $this->modPow($m, $e, $n);
        }

        return $blocks;
    }

    public function decrypt(array $blocks, int|string $n, int|string $d): string
    {
        $n = (int) $n;
        $d = (int) $d;
        $plaintext = '';

        foreach ($blocks as $block) {
            // Decryption applies the RSA formula directly: m = c^d mod n.
            $m = $this->modPow((int) $block, $d, $n);
            $plaintext .= $this->numberToText($m);
        }

        return $plaintext;
    }

    public function generatePrime(): int
    {
        do {
            $candidate = random_int(self::MIN_PRIME, self::MAX_PRIME);
            if ($candidate % 2 === 0) {
                $candidate++;
            }
        } while (!$this->isPrime($candidate));

        return $candidate;
    }

    public function isPrime(int $number): bool
    {
        if ($number < 2) {
            return false;
        }
        if ($number === 2) {
            return true;
        }
        if ($number % 2 === 0) {
            return false;
        }

        $limit = (int) floor(sqrt($number));
        for ($i = 3; $i <= $limit; $i += 2) {
            if ($number % $i === 0) {
                return false;
            }
        }

        return true;
    }

    public function gcd(int $a, int $b): int
    {
        while ($b !== 0) {
            $temp = $b;
            $b = $a % $b;
            $a = $temp;
        }

        return abs($a);
    }

    public function extendedGcd(int $a, int $b): array
    {
        if ($b === 0) {
            return [$a, 1, 0];
        }

        [$gcd, $x1, $y1] = $this->extendedGcd($b, $a % $b);

        return [$gcd, $y1, $x1 - intdiv($a, $b) * $y1];
    }

    public function modInverse(int $a, int $modulus): int
    {
        [$gcd, $x] = $this->extendedGcd($a, $modulus);
        if ($gcd !== 1) {
            throw new RuntimeException('Modular inverse does not exist.');
        }

        return ($x % $modulus + $modulus) % $modulus;
    }

    public function modPow(int $base, int $exponent, int $modulus): int
    {
        if ($modulus === 1) {
            return 0;
        }

        $result = 1;
        $base %= $modulus;

        while ($exponent > 0) {
            if ($exponent % 2 === 1) {
                $result = $this->mulMod($result, $base, $modulus);
            }

            $exponent = intdiv($exponent, 2);
            $base = $this->mulMod($base, $base, $modulus);
        }

        return $result;
    }

    private function mulMod(int $a, int $b, int $modulus): int
    {
        $result = 0;
        $a %= $modulus;

        while ($b > 0) {
            if ($b % 2 === 1) {
                $result = ($result + $a) % $modulus;
            }

            $a = ($a * 2) % $modulus;
            $b = intdiv($b, 2);
        }

        return $result;
    }

    private function textToNumber(string $text): int
    {
        $number = 0;
        $length = strlen($text);

        for ($i = 0; $i < $length; $i++) {
            $number = ($number * 256) + ord($text[$i]);
        }

        return $number;
    }

    private function numberToText(int $number): string
    {
        if ($number === 0) {
            return '';
        }

        $bytes = [];
        while ($number > 0) {
            array_unshift($bytes, chr($number % 256));
            $number = intdiv($number, 256);
        }

        return implode('', $bytes);
    }

    private function blockSize(int $n): int
    {
        $size = 0;
        $limit = 1;

        while (($limit * 256) < $n) {
            $limit *= 256;
            $size++;
        }

        return max(1, $size);
    }
}
