<?php

namespace App\Services\Authenticators;

use App\Data\AuthenticatorData;
use App\Exceptions\VaultEncryptionException;
use App\Models\Authenticator;
use App\Models\Vault;
use App\Services\Vaults\VaultKeyService;
use JsonException;

final readonly class AuthenticatorCipher
{
    public function __construct(private VaultKeyService $keys) {}

    /** @return array{encrypted_payload: string, nonce: string, encryption_version: int, fingerprint: string} */
    public function encrypt(Vault $vault, string $uuid, #[\SensitiveParameter] AuthenticatorData $data): array
    {
        $version = (int) config('authenticators.cipher_version', 1);
        $nonce = random_bytes(SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_NPUBBYTES);
        $payload = json_encode($data->toArray(), JSON_THROW_ON_ERROR);
        $ciphertext = sodium_crypto_aead_xchacha20poly1305_ietf_encrypt(
            $payload,
            $this->associatedData($vault, $uuid, $version),
            $nonce,
            $this->keys->getOrCreate($vault),
        );

        return [
            'encrypted_payload' => base64_encode($ciphertext),
            'nonce' => base64_encode($nonce),
            'encryption_version' => $version,
            'fingerprint' => $this->fingerprint($vault, $data),
        ];
    }

    public function decrypt(Authenticator $authenticator): AuthenticatorData
    {
        $authenticator->loadMissing('vault');
        $payload = sodium_crypto_aead_xchacha20poly1305_ietf_decrypt(
            $this->decode($authenticator->encrypted_payload),
            $this->associatedData($authenticator->vault, $authenticator->uuid, $authenticator->encryption_version),
            $this->decode($authenticator->nonce),
            $this->keys->getOrCreate($authenticator->vault),
        );

        if ($payload === false) {
            throw VaultEncryptionException::decryptionFailed();
        }

        try {
            $decoded = json_decode($payload, true, flags: JSON_THROW_ON_ERROR);

            return AuthenticatorData::fromArray($decoded);
        } catch (JsonException|\ValueError|\TypeError) {
            throw VaultEncryptionException::decryptionFailed();
        } finally {
            if (isset($payload)) {
                sodium_memzero($payload);
            }
        }
    }

    public function fingerprint(Vault $vault, #[\SensitiveParameter] AuthenticatorData $data): string
    {
        $message = implode("\0", [
            (string) $vault->workspace_id,
            $data->secret,
            $data->algorithm->value,
            (string) $data->digits->value,
            (string) $data->period,
        ]);

        return hash_hmac('sha256', $message, $this->keys->fingerprintKey());
    }

    private function associatedData(Vault $vault, string $uuid, int $version): string
    {
        return "authenticator:v{$version}:{$vault->workspace_id}:{$vault->getKey()}:{$uuid}";
    }

    private function decode(string $encoded): string
    {
        $decoded = base64_decode($encoded, true);
        if ($decoded === false) {
            throw VaultEncryptionException::decryptionFailed();
        }

        return $decoded;
    }
}
