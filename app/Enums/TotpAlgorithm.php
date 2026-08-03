<?php

namespace App\Enums;

enum TotpAlgorithm: string
{
    case Sha1 = 'SHA1';
    case Sha256 = 'SHA256';
    case Sha512 = 'SHA512';

    public function digest(): string
    {
        return strtolower($this->value);
    }
}
