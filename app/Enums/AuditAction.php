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
    case InvitationCreated = 'invitation.created';
    case InvitationAccepted = 'invitation.accepted';
    case InvitationRevoked = 'invitation.revoked';
    case InvitationExpired = 'invitation.expired';
    case MemberAccessUpdated = 'member.access_updated';
    case MemberRemoved = 'member.removed';
    case VaultAccessGranted = 'vault.access_granted';
    case VaultAccessRevoked = 'vault.access_revoked';
    case RotationTaskCreated = 'rotation_task.created';
}
