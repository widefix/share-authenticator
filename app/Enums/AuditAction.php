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
    case AuthenticatorCreated = 'authenticator.created';
    case AuthenticatorUpdated = 'authenticator.updated';
    case AuthenticatorMoved = 'authenticator.moved';
    case AuthenticatorSecretReplaced = 'authenticator.secret_replaced';
    case AuthenticatorArchived = 'authenticator.archived';
    case AuthenticatorRestored = 'authenticator.restored';
    case AuthenticatorDeleted = 'authenticator.deleted';
    case AuthenticatorRevealed = 'authenticator.revealed';
    case AuthenticatorCopied = 'authenticator.copied';
}
