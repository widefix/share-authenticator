<?php

namespace App\Services\Workspaces;

use App\Enums\AuditAction;
use App\Enums\WorkspaceRole;
use App\Models\User;
use App\Models\Workspace;
use App\Services\Audit\AuditLogger;
use App\Services\Shared\NameNormalizer;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class WorkspaceService
{
    public const DEFAULT_VAULT_NAME = 'General';

    public function __construct(
        private NameNormalizer $names,
        private AuditLogger $audit,
    ) {}

    public function create(User $user, string $name): Workspace
    {
        try {
            return DB::transaction(function () use ($user, $name): Workspace {
                $lockedUser = User::query()->lockForUpdate()->findOrFail($user->getKey());

                if ($lockedUser->workspace_id !== null) {
                    throw ValidationException::withMessages([
                        'name' => 'You already belong to a workspace.',
                    ]);
                }

                $workspace = Workspace::query()->create([
                    'name' => $this->names->display($name),
                    'created_by_user_id' => $lockedUser->getKey(),
                ]);

                $workspace->vaults()->create([
                    'name' => self::DEFAULT_VAULT_NAME,
                    'normalized_name' => $this->names->normalize(self::DEFAULT_VAULT_NAME),
                ]);

                $lockedUser->forceFill([
                    'workspace_id' => $workspace->getKey(),
                    'role' => WorkspaceRole::Admin,
                ])->save();

                $this->audit->record(
                    $workspace,
                    $lockedUser,
                    AuditAction::WorkspaceCreated,
                    $workspace,
                );

                return $workspace;
            });
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages([
                'name' => 'You already belong to a workspace.',
            ]);
        }
    }
}
