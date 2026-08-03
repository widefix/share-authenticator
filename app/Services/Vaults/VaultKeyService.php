<?php

namespace App\Services\Vaults;

use App\Exceptions\VaultEncryptionException;
use App\Models\Vault;
use App\Models\VaultEncryptionKey;
use Illuminate\Database\UniqueConstraintViolationException;

final class VaultKeyService
{
    public function getOrCreate(Vault $vault): string
    {
        $stored = VaultEncryptionKey::query()->where('vault_id', $vault->getKey())->first();
        if ($stored) {
            return $this->unwrap($stored);
        }

        $key = random_bytes(SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_KEYBYTES);
        $nonce = random_bytes(SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_NPUBBYTES);
        $wrapped = sodium_crypto_aead_xchacha20poly1305_ietf_encrypt(
            $key,
            $this->associatedData($vault->getKey(), 1),
            $nonce,
            $this->masterKey(),
        );

        try {
            VaultEncryptionKey::query()->create([
                'vault_id' => $vault->getKey(),
                'version' => 1,
                'wrapped_key' => base64_encode($wrapped),
                'nonce' => base64_encode($nonce),
            ]);

            return $key;
        } catch (UniqueConstraintViolationException) {
            return $this->unwrap(VaultEncryptionKey::query()->where('vault_id', $vault->getKey())->firstOrFail());
        }
    }

    public function fingerprintKey(): string
    {
        return hash_hkdf('sha256', $this->masterKey(), 32, 'authenticator-duplicate-v1');
    }

    private function unwrap(VaultEncryptionKey $stored): string
    {
        $key = sodium_crypto_aead_xchacha20poly1305_ietf_decrypt(
            $this->decode($stored->wrapped_key),
            $this->associatedData($stored->vault_id, $stored->version),
            $this->decode($stored->nonce),
            $this->masterKey(),
        );

        if ($key === false || strlen($key) !== SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_KEYBYTES) {
            throw VaultEncryptionException::decryptionFailed();
        }

        return $key;
    }

    private function masterKey(): string
    {
        $configured = config('authenticators.encryption_key');
        if (! is_string($configured) || $configured === '') {
            throw VaultEncryptionException::missingKey();
        }

        $encoded = str_starts_with($configured, 'base64:') ? substr($configured, 7) : $configured;
        $decoded = base64_decode($encoded, true);
        if ($decoded === false || strlen($decoded) !== SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_KEYBYTES) {
            throw VaultEncryptionException::invalidKey();
        }

        return $decoded;
    }

    private function associatedData(int $vaultId, int $version): string
    {
        return "vault-key:v{$version}:{$vaultId}";
    }

    private function decode(string $value): string
    {
        $decoded = base64_decode($value, true);
        if ($decoded === false) {
            throw VaultEncryptionException::decryptionFailed();
        }

        return $decoded;
    }
}
