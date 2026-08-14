<?php

namespace App\Models;

use App\Enums\WorkspaceRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;

#[Fillable(['email', 'password'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'role' => WorkspaceRole::class,
        ];
    }

    protected $hidden = [
        'password',
    ];

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function isWorkspaceAdmin(): bool
    {
        return $this->role === WorkspaceRole::Admin;
    }

    public function vaults(): BelongsToMany
    {
        return $this->belongsToMany(Vault::class)
            ->withPivot('granted_by_user_id')
            ->withTimestamps();
    }

    public function sentWorkspaceInvitations(): HasMany
    {
        return $this->hasMany(WorkspaceInvitation::class, 'invited_by_user_id');
    }

    public function canAccessVault(Vault $vault): bool
    {
        if ($this->workspace_id !== $vault->workspace_id) {
            return false;
        }

        return $this->isWorkspaceAdmin()
            || $this->vaults()->whereKey($vault->getKey())->exists();
    }
}
