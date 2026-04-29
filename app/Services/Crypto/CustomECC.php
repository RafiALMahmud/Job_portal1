<?php

namespace App\Services\Crypto;

use RuntimeException;

class CustomECC
{
    public const CURVE_NAME = 'LAB_CUSTOM_CURVE';

    // Educational curve over a finite field: y^2 = x^3 + ax + b mod p.
    // p is the prime field size, a and b shape the curve, and G is a base
    // point used to create public keys. These small parameters are for a lab
    // demo only; production ECC needs standardized large curves.
    private int $p = 2147483647;
    private int $a = 2;
    private int $b = 3;
    private int $k = 256;
    private int $scalarLimit = 2147483000;
    private array $g;

    public function __construct()
    {
        if ($this->mod(4 * ($this->a ** 3) + 27 * ($this->b ** 2), $this->p) === 0) {
            throw new RuntimeException('Invalid elliptic curve parameters.');
        }

        $this->g = $this->findBasePoint();
    }

    public function curveName(): string
    {
        return self::CURVE_NAME;
    }

    public function basePoint(): array
    {
        return $this->g;
    }

    public function generateKeyPair(): array
    {
        // The private key d is a random scalar. The public key Q is dG, meaning
        // the base point G added to itself d times using scalar multiplication.
        $private = random_int(2, $this->scalarLimit);
        $public = $this->scalarMultiply($private, $this->g);

        return [
            'private_d' => (string) $private,
            'public_x' => (string) $public['x'],
            'public_y' => (string) $public['y'],
            'curve_name' => self::CURVE_NAME,
        ];
    }

    public function encrypt(string $plaintext, array $publicKey): array
    {
        $blocks = [];

        foreach ($this->textChunks($plaintext) as $chunk) {
            $messagePoint = $this->messageToPoint($chunk);
            $k = random_int(2, $this->scalarLimit);

            // ECC ElGamal stores C1 = kG and C2 = M + kQ. Only the owner of
            // private key d can compute dC1 = kQ and subtract it to recover M.
            $c1 = $this->scalarMultiply($k, $this->g);
            $sharedSecret = $this->scalarMultiply($k, $publicKey);
            $c2 = $this->pointAdd($messagePoint, $sharedSecret);

            $blocks[] = [
                'c1' => $this->serializePoint($c1),
                'c2' => $this->serializePoint($c2),
                'length' => strlen($chunk),
            ];
        }

        return [
            'algorithm' => 'CUSTOM_ECC_ELGAMAL',
            'curve' => self::CURVE_NAME,
            'blocks' => $blocks,
        ];
    }

    public function decrypt(array $payload, int|string $privateKey): string
    {
        if (($payload['algorithm'] ?? null) !== 'CUSTOM_ECC_ELGAMAL') {
            throw new RuntimeException('Unsupported ECC message algorithm.');
        }

        $plaintext = '';
        foreach ($payload['blocks'] ?? [] as $block) {
            $c1 = $this->pointFromPayload($block['c1'] ?? null);
            $c2 = $this->pointFromPayload($block['c2'] ?? null);

            // Decryption computes M = C2 - dC1. dC1 equals kQ, the same shared
            // point that was added during encryption, so subtracting it restores
            // the encoded plaintext point. Plaintext is never stored.
            $sharedSecret = $this->scalarMultiply((int) $privateKey, $c1);
            $messagePoint = $this->pointAdd($c2, $this->negativePoint($sharedSecret));
            $plaintext .= $this->pointToMessage($messagePoint, (int) ($block['length'] ?? 0));
        }

        return $plaintext;
    }

    public function isValidPoint(?array $point): bool
    {
        if ($this->isInfinity($point)) {
            return true;
        }

        if (!isset($point['x'], $point['y'])) {
            return false;
        }

        $x = (int) $point['x'];
        $y = (int) $point['y'];
        $left = $this->mod($this->mulMod($y, $y), $this->p);
        $right = $this->mod($this->mulMod($this->mulMod($x, $x), $x) + ($this->a * $x) + $this->b, $this->p);

        return $left === $right;
    }

    public function pointAdd(?array $p1, ?array $p2): ?array
    {
        if ($this->isInfinity($p1)) {
            return $p2;
        }
        if ($this->isInfinity($p2)) {
            return $p1;
        }

        $x1 = (int) $p1['x'];
        $y1 = (int) $p1['y'];
        $x2 = (int) $p2['x'];
        $y2 = (int) $p2['y'];

        if ($x1 === $x2 && $this->mod($y1 + $y2, $this->p) === 0) {
            return null;
        }

        if ($x1 === $x2 && $y1 === $y2) {
            return $this->pointDouble($p1);
        }

        $slope = $this->mod(
            $this->mulMod($this->mod($y2 - $y1, $this->p), $this->modInverse($this->mod($x2 - $x1, $this->p), $this->p)),
            $this->p
        );

        $x3 = $this->mod($this->mulMod($slope, $slope) - $x1 - $x2, $this->p);
        $y3 = $this->mod($this->mulMod($slope, $this->mod($x1 - $x3, $this->p)) - $y1, $this->p);

        return ['x' => $x3, 'y' => $y3];
    }

    public function pointDouble(?array $point): ?array
    {
        if ($this->isInfinity($point)) {
            return null;
        }

        $x = (int) $point['x'];
        $y = (int) $point['y'];
        if ($y === 0) {
            return null;
        }

        $numerator = $this->mod(3 * $this->mulMod($x, $x) + $this->a, $this->p);
        $denominator = $this->modInverse($this->mod(2 * $y, $this->p), $this->p);
        $slope = $this->mulMod($numerator, $denominator);

        $x3 = $this->mod($this->mulMod($slope, $slope) - (2 * $x), $this->p);
        $y3 = $this->mod($this->mulMod($slope, $this->mod($x - $x3, $this->p)) - $y, $this->p);

        return ['x' => $x3, 'y' => $y3];
    }

    public function scalarMultiply(int $scalar, ?array $point): ?array
    {
        $result = null;
        $addend = $point;

        while ($scalar > 0) {
            if ($scalar % 2 === 1) {
                $result = $this->pointAdd($result, $addend);
            }

            $addend = $this->pointDouble($addend);
            $scalar = intdiv($scalar, 2);
        }

        return $result;
    }

    public function negativePoint(?array $point): ?array
    {
        if ($this->isInfinity($point)) {
            return null;
        }

        return ['x' => (int) $point['x'], 'y' => $this->mod(-(int) $point['y'], $this->p)];
    }

    public function modInverse(int $value, int $modulus): int
    {
        [$gcd, $x] = $this->extendedGcd($value, $modulus);
        if ($gcd !== 1) {
            throw new RuntimeException('ECC modular inverse does not exist.');
        }

        return $this->mod($x, $modulus);
    }

    public function extendedGcd(int $a, int $b): array
    {
        if ($b === 0) {
            return [$a, 1, 0];
        }

        [$gcd, $x1, $y1] = $this->extendedGcd($b, $a % $b);

        return [$gcd, $y1, $x1 - intdiv($a, $b) * $y1];
    }

    public function modPow(int $base, int $exponent, int $modulus): int
    {
        $result = 1;
        $base = $this->mod($base, $modulus);

        while ($exponent > 0) {
            if ($exponent % 2 === 1) {
                $result = $this->mulMod($result, $base, $modulus);
            }

            $base = $this->mulMod($base, $base, $modulus);
            $exponent = intdiv($exponent, 2);
        }

        return $result;
    }

    private function messageToPoint(string $chunk): array
    {
        $m = $this->textToNumber($chunk);

        for ($j = 0; $j < $this->k; $j++) {
            $x = ($m * $this->k) + $j;
            if ($x >= $this->p) {
                break;
            }

            $rhs = $this->mod($this->mulMod($this->mulMod($x, $x), $x) + ($this->a * $x) + $this->b, $this->p);
            $y = $this->sqrtMod($rhs);
            if ($y !== null) {
                return ['x' => $x, 'y' => $y];
            }
        }

        throw new RuntimeException('Unable to encode message chunk as a curve point.');
    }

    private function pointToMessage(?array $point, int $length): string
    {
        if (!$this->isValidPoint($point) || $this->isInfinity($point)) {
            throw new RuntimeException('Invalid decrypted ECC message point.');
        }

        $m = intdiv((int) $point['x'], $this->k);

        return $this->numberToText($m, $length);
    }

    private function sqrtMod(int $value): ?int
    {
        if ($value === 0) {
            return 0;
        }

        // For p % 4 == 3, square root can be found as value^((p + 1) / 4) mod p.
        $root = $this->modPow($value, intdiv($this->p + 1, 4), $this->p);

        return $this->mulMod($root, $root, $this->p) === $value ? $root : null;
    }

    private function findBasePoint(): array
    {
        for ($x = 2; $x < 1000; $x++) {
            $rhs = $this->mod($this->mulMod($this->mulMod($x, $x), $x) + ($this->a * $x) + $this->b, $this->p);
            $y = $this->sqrtMod($rhs);
            if ($y !== null) {
                return ['x' => $x, 'y' => $y];
            }
        }

        throw new RuntimeException('Unable to find an ECC base point.');
    }

    private function textChunks(string $text): array
    {
        return str_split($text, 2);
    }

    private function textToNumber(string $text): int
    {
        $number = 0;
        for ($i = 0; $i < strlen($text); $i++) {
            $number = ($number * 256) + ord($text[$i]);
        }

        return $number;
    }

    private function numberToText(int $number, int $length): string
    {
        $text = '';
        for ($i = 0; $i < $length; $i++) {
            $text = chr($number % 256) . $text;
            $number = intdiv($number, 256);
        }

        return $text;
    }

    private function pointFromPayload(mixed $payload): ?array
    {
        if ($payload === null || ($payload['infinity'] ?? false)) {
            return null;
        }

        $point = ['x' => (int) $payload['x'], 'y' => (int) $payload['y']];
        if (!$this->isValidPoint($point)) {
            throw new RuntimeException('Ciphertext contains an invalid ECC point.');
        }

        return $point;
    }

    private function serializePoint(?array $point): array
    {
        if ($this->isInfinity($point)) {
            return ['infinity' => true];
        }

        return ['x' => (string) $point['x'], 'y' => (string) $point['y']];
    }

    private function isInfinity(?array $point): bool
    {
        return $point === null || ($point['infinity'] ?? false);
    }

    private function mulMod(int $a, int $b, ?int $modulus = null): int
    {
        $modulus ??= $this->p;
        $result = 0;
        $a = $this->mod($a, $modulus);

        while ($b > 0) {
            if ($b % 2 === 1) {
                $result = $this->mod($result + $a, $modulus);
            }

            $a = $this->mod($a * 2, $modulus);
            $b = intdiv($b, 2);
        }

        return $result;
    }

    private function mod(int $value, int $modulus): int
    {
        return ($value % $modulus + $modulus) % $modulus;
    }
}
