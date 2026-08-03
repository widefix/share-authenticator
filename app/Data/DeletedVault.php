<?php

namespace App\Data;

final readonly class DeletedVault
{
    public function __construct(
        public int $authenticatorCount,
        public int $memberCount,
    ) {}
}
