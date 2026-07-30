<?php

namespace App\Data;

use App\Enums\LoginCodeVerificationStatus;
use App\Models\User;

final readonly class LoginCodeVerification
{
    public function __construct(
        public LoginCodeVerificationStatus $status,
        public ?User $user = null,
    ) {}
}
