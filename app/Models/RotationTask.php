<?php

namespace App\Models;

use App\Enums\RotationReason;
use App\Enums\RotationTaskStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'uuid',
    'workspace_id',
    'vault_id',
    'authenticator_id',
    'affected_user_id',
    'triggered_by_user_id',
    'reason',
    'status',
    'completed_at',
    'risk_accepted_at',
])]
class RotationTask extends Model
{
    protected function casts(): array
    {
        return [
            'reason' => RotationReason::class,
            'status' => RotationTaskStatus::class,
            'completed_at' => 'datetime',
            'risk_accepted_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function vault(): BelongsTo
    {
        return $this->belongsTo(Vault::class);
    }

    public function authenticator(): BelongsTo
    {
        return $this->belongsTo(Authenticator::class);
    }

    public function affectedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'affected_user_id');
    }
}
