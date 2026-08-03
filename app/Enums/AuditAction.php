<?php

namespace App\Enums;

enum AuditAction: string
{
    case WorkspaceCreated = 'workspace.created';
    case VaultCreated = 'vault.created';
    case VaultRenamed = 'vault.renamed';
    case VaultArchived = 'vault.archived';
    case VaultRestored = 'vault.restored';
    case VaultDeleted = 'vault.deleted';
}
