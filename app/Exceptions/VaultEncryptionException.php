<?php

namespace App\Exceptions;

use RuntimeException;

final class VaultEncryptionException extends RuntimeException
{
    public static function missingKey(): self
    {
        return new self('Vault encryption is not configured. Set VAULT_ENCRYPTION_KEY.');
    }

    public static function invalidKey(): self
    {
        return new self('VAULT_ENCRYPTION_KEY must be a base64-encoded 32-byte key.');
    }

    public static function decryptionFailed(): self
    {
        return new self('The encrypted authenticator data could not be opened.');
    }
}
