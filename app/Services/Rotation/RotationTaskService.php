<?php

namespace App\Services\Rotation;

use App\Enums\AuditAction;
use App\Enums\RotationReason;
use App\Enums\RotationTaskStatus;
use App\Models\Authenticator;
use App\Models\RotationTask;
use App\Models\User;
use App\Models\Vault;
use App\Services\Audit\AuditLogger;
use Illuminate\Support\Str;

final readonly class RotationTaskService
{
    public function __construct(private AuditLogger $audit) {}

    /** @param iterable<int, Vault> $vaults */
    public function createForRevokedVaults(
        User $actor,
        User $affectedUser,
        iterable $vaults,
        RotationReason $reason,
    ): int {
        $created = 0;

        foreach ($vaults as $vault) {
            $vault->authenticators()->each(function (Authenticator $authenticator) use ($actor, $affectedUser, $vault, $reason, &$created): void {
                $task = RotationTask::query()->firstOrCreate(
                    [
                        'authenticator_id' => $authenticator->getKey(),
                        'affected_user_id' => $affectedUser->getKey(),
                        'reason' => $reason,
                        'status' => RotationTaskStatus::Open,
                    ],
                    [
                        'uuid' => (string) Str::uuid(),
                        'workspace_id' => $vault->workspace_id,
                        'vault_id' => $vault->getKey(),
                        'triggered_by_user_id' => $actor->getKey(),
                    ],
                );

                if ($task->wasRecentlyCreated) {
                    $created++;
                    $this->audit->record($actor->workspace, $actor, AuditAction::RotationTaskCreated, $task);
                }
            });
        }

        return $created;
    }
}
