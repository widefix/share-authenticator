<?php

namespace App\Enums;

enum LoginCodeVerificationStatus
{
    case Valid;
    case Invalid;
    case Expired;
    case Locked;
}
