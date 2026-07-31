<?php

namespace App\Data;

final readonly class IssuedLoginCode
{
    public function __construct(
        public string $email,
        public string $generation,
    ) {}
}
