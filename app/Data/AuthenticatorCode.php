<?php

namespace App\Data;

use Carbon\CarbonImmutable;

final readonly class AuthenticatorCode
{
    public function __construct(
        #[\SensitiveParameter]
        public string $code,
        public CarbonImmutable $expiresAt,
        public CarbonImmutable $serverTime,
        public int $digits,
        public int $period,
    ) {}
}
