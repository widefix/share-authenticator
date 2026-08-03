<?php

namespace App\Services\Audit;

use App\Enums\AuditAction;
use App\Models\AuditEvent;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Model;

final class AuditLogger
{
    public function record(
        Workspace $workspace,
        User $actor,
        AuditAction $action,
        Model $subject,
    ): AuditEvent {
        return AuditEvent::query()->create([
            'workspace_id' => $workspace->getKey(),
            'actor_user_id' => $actor->getKey(),
            'action' => $action,
            'subject_type' => $subject->getMorphClass(),
            'subject_id' => $subject->getKey(),
            'created_at' => now(),
        ]);
    }
}
