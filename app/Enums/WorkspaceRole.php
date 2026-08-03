<?php

namespace App\Enums;

enum WorkspaceRole: string
{
    case Admin = 'admin';
    case Member = 'member';
}
