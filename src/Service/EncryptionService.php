<?php

declare(strict_types=1);

namespace App\Service;

use RuntimeException;

final class EncryptionService
{
    private string $key;

    public function __construct(string $encodedKey)
    {
        $key = base64_decode($encodedKey, true);

        if (
            $key === false
            || strlen($key) !== SODIUM_CRYPTO_SECRETBOX_KEYBYTES
        ) {
            throw new RuntimeException(
                'APP_KEY ist nicht gültig konfiguriert.'
            );
        }

        $this->key = $key;
    }

    public function encrypt(string $plaintext): string
    {
        $nonce = random_bytes(
            SODIUM_CRYPTO_SECRETBOX_NONCEBYTES
        );

        $ciphertext = sodium_crypto_secretbox(
            $plaintext,
            $nonce,
            $this->key
        );

        return 'v1:' . base64_encode(
            $nonce . $ciphertext
        );
    }

    public function decrypt(string $encryptedValue): string
    {
        if (!str_starts_with($encryptedValue, 'v1:')) {
            throw new RuntimeException(
                'Unbekanntes Verschlüsselungsformat.'
            );
        }

        $encodedValue = substr($encryptedValue, 3);

        $decodedValue = base64_decode(
            $encodedValue,
            true
        );

        if (
            $decodedValue === false
            || strlen($decodedValue)
                <= SODIUM_CRYPTO_SECRETBOX_NONCEBYTES
        ) {
            throw new RuntimeException(
                'Der verschlüsselte Wert ist ungültig.'
            );
        }

        $nonce = substr(
            $decodedValue,
            0,
            SODIUM_CRYPTO_SECRETBOX_NONCEBYTES
        );

        $ciphertext = substr(
            $decodedValue,
            SODIUM_CRYPTO_SECRETBOX_NONCEBYTES
        );

        $plaintext = sodium_crypto_secretbox_open(
            $ciphertext,
            $nonce,
            $this->key
        );

        if ($plaintext === false) {
            throw new RuntimeException(
                'Der Wert konnte nicht entschlüsselt werden.'
            );
        }

        return $plaintext;
    }
}