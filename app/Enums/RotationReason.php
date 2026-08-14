<?php

namespace App\Enums;

enum RotationReason: string
{
    case MemberRemoved = 'member_removed';
    case VaultAccessRemoved = 'vault_access_removed';
}
