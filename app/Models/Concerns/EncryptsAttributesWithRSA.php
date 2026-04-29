<?php

namespace App\Models\Concerns;

use App\Services\Crypto\RSAFieldCrypto;
use App\Services\Crypto\RecordMac;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

trait EncryptsAttributesWithRSA
{
    protected function rsaEncryptedFields(): array
    {
        return property_exists($this, 'rsaEncrypted') ? $this->rsaEncrypted : [];
    }

    public function rsaEncryptedFieldsForCommand(): array
    {
        return $this->rsaEncryptedFields();
    }

    public function setAttribute($key, $value)
    {
        if (in_array($key, $this->rsaEncryptedFields(), true)) {
            $value = app(RSAFieldCrypto::class)->encryptField($value === null ? null : (string) $value);
        }

        return parent::setAttribute($key, $value);
    }

    public function getAttributeValue($key)
    {
        $value = parent::getAttributeValue($key);

        if (in_array($key, $this->rsaEncryptedFields(), true)) {
            if (!$this->encryptedPayloadMacIsValid()) {
                return 'Encrypted data integrity check failed. This record may have been modified.';
            }

            return app(RSAFieldCrypto::class)->decryptField($value);
        }

        return $value;
    }

    public function getEncryptedAttributeValue(string $key): mixed
    {
        return $this->attributes[$key] ?? null;
    }

    protected static function bootEncryptsAttributesWithRSA(): void
    {
        static::saving(function ($model) {
            if (Schema::hasColumn($model->getTable(), 'encrypted_payload_mac')) {
                $model->attributes['encrypted_payload_mac'] = app(RecordMac::class)->sign($model->encryptedPayloadForMac());
            }
        });
    }

    public function encryptedPayloadForMac(): string
    {
        $payload = [];
        foreach ($this->rsaEncryptedFields() as $field) {
            $payload[$field] = $this->attributes[$field] ?? null;
        }

        ksort($payload);

        return json_encode($payload);
    }

    public function encryptedPayloadMacIsValid(): bool
    {
        if (!Schema::hasColumn($this->getTable(), 'encrypted_payload_mac')) {
            return true;
        }

        $mac = $this->attributes['encrypted_payload_mac'] ?? null;
        if ($mac === null || $mac === '') {
            return false;
        }

        try {
            return app(RecordMac::class)->verify($this->encryptedPayloadForMac(), $mac);
        } catch (RuntimeException) {
            return false;
        }
    }
}
