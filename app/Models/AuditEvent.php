<?php

namespace App\Models;

use App\Enums\AuditAction;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

#[Fillable([
    'workspace_id',
    'actor_user_id',
    'action',
    'subject_type',
    'subject_id',
    'created_at',
])]
class AuditEvent extends Model
{
    public const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'action' => AuditAction::class,
            'created_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Audit events are immutable.'));
        static::deleting(fn () => throw new LogicException('Audit events are immutable.'));
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }
}
