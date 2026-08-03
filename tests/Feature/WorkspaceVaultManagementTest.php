<?php

namespace Tests\Feature;

use App\Enums\AuditAction;
use App\Enums\WorkspaceRole;
use App\Models\AuditEvent;
use App\Models\User;
use App\Models\Vault;
use App\Models\Workspace;
use App\Services\Workspaces\WorkspaceService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Tests\TestCase;

class WorkspaceVaultManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_supports_a_user_without_a_workspace(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Create workspace')
            ->assertSee(route('workspaces.store'), escape: false)
            ->assertViewHas('user', fn (User $viewUser): bool => $viewUser->is($user))
            ->assertViewHas('vaults', fn ($vaults): bool => $vaults->isEmpty());
    }

    public function test_workspace_creation_assigns_admin_and_creates_default_vault_and_audit_event(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('workspaces.store'), ['name' => '  Acme   Team  '])
            ->assertRedirect(route('dashboard'));

        $user->refresh();
        $workspace = $user->workspace;

        $this->assertNotNull($workspace);
        $this->assertSame('Acme Team', $workspace->name);
        $this->assertSame(WorkspaceRole::Admin, $user->role);
        $this->assertDatabaseHas('vaults', [
            'workspace_id' => $workspace->getKey(),
            'name' => WorkspaceService::DEFAULT_VAULT_NAME,
            'normalized_name' => 'general',
        ]);
        $this->assertDatabaseHas('audit_events', [
            'workspace_id' => $workspace->getKey(),
            'actor_user_id' => $user->getKey(),
            'action' => AuditAction::WorkspaceCreated->value,
            'subject_type' => Workspace::class,
            'subject_id' => $workspace->getKey(),
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Acme Team')
            ->assertSee('General')
            ->assertSee('Manage vaults');
    }

    public function test_user_cannot_create_a_second_workspace(): void
    {
        [$user] = $this->createWorkspace();

        $this->actingAs($user)
            ->post(route('workspaces.store'), ['name' => 'Another workspace'])
            ->assertForbidden();

        $this->assertSame(1, Workspace::query()->count());
    }

    public function test_database_prevents_multiple_workspaces_created_by_one_user(): void
    {
        $user = User::factory()->create();

        Workspace::factory()->create(['created_by_user_id' => $user->getKey()]);

        $this->expectException(UniqueConstraintViolationException::class);

        Workspace::factory()->create(['created_by_user_id' => $user->getKey()]);
    }

    public function test_vault_names_are_normalized_and_unique_within_a_workspace(): void
    {
        [$admin, $workspace] = $this->createWorkspace();

        $this->actingAs($admin)
            ->post(route('vaults.store'), ['name' => '  Client   Support  '])
            ->assertRedirect(route('vaults.index'));

        $this->assertDatabaseHas('vaults', [
            'workspace_id' => $workspace->getKey(),
            'name' => 'Client Support',
            'normalized_name' => 'client support',
        ]);

        $this->actingAs($admin)
            ->post(route('vaults.store'), ['name' => 'client support'])
            ->assertSessionHasErrors('name');

        $this->assertSame(1, Vault::query()->where('normalized_name', 'client support')->count());
    }

    public function test_different_workspaces_can_use_the_same_vault_name(): void
    {
        [$firstAdmin] = $this->createWorkspace('First');
        [$secondAdmin] = $this->createWorkspace('Second');

        $this->actingAs($firstAdmin)
            ->post(route('vaults.store'), ['name' => 'Finance'])
            ->assertRedirect(route('vaults.index'));

        $this->actingAs($secondAdmin)
            ->post(route('vaults.store'), ['name' => 'Finance'])
            ->assertRedirect(route('vaults.index'));

        $this->assertSame(2, Vault::query()->where('normalized_name', 'finance')->count());
    }

    public function test_admin_can_manage_a_vault_through_its_full_lifecycle(): void
    {
        [$admin, $workspace] = $this->createWorkspace();

        $this->actingAs($admin)->post(route('vaults.store'), ['name' => 'Finance']);
        $vault = $workspace->vaults()->where('normalized_name', 'finance')->firstOrFail();

        $this->actingAs($admin)
            ->patch(route('vaults.update', $vault), ['name' => 'Accounting'])
            ->assertRedirect(route('vaults.index'));

        $this->actingAs($admin)
            ->post(route('vaults.archive', $vault))
            ->assertRedirect(route('vaults.index'));

        $this->assertNotNull($vault->fresh()->archived_at);
        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertViewHas(
                'vaults',
                fn ($vaults): bool => ! $vaults->contains('id', $vault->getKey()),
            );

        $this->actingAs($admin)
            ->get(route('vaults.index'))
            ->assertOk()
            ->assertSee('Archived vaults')
            ->assertSee('Accounting')
            ->assertSee('0 authenticators')
            ->assertSee('1 workspace member');

        $this->actingAs($admin)
            ->delete(route('vaults.restore', $vault))
            ->assertRedirect(route('vaults.index'));

        $this->assertNull($vault->fresh()->archived_at);

        $this->actingAs($admin)
            ->delete(route('vaults.destroy', $vault), ['confirmation' => 'Accounting'])
            ->assertRedirect(route('vaults.index'));

        $this->assertDatabaseMissing('vaults', ['id' => $vault->getKey()]);
        $this->assertSame([
            AuditAction::WorkspaceCreated->value,
            AuditAction::VaultCreated->value,
            AuditAction::VaultRenamed->value,
            AuditAction::VaultArchived->value,
            AuditAction::VaultRestored->value,
            AuditAction::VaultDeleted->value,
        ], AuditEvent::query()->orderBy('id')->pluck('action')->map->value->all());
    }

    public function test_last_active_vault_cannot_be_archived_or_deleted(): void
    {
        [$admin, $workspace] = $this->createWorkspace();
        $vault = $workspace->vaults()->firstOrFail();

        $this->actingAs($admin)
            ->post(route('vaults.archive', $vault))
            ->assertSessionHasErrors('vault');

        $this->actingAs($admin)
            ->delete(route('vaults.destroy', $vault), ['confirmation' => 'General'])
            ->assertSessionHasErrors('vault');

        $this->assertDatabaseHas('vaults', [
            'id' => $vault->getKey(),
            'archived_at' => null,
        ]);
    }

    public function test_vault_deletion_requires_exact_name_confirmation(): void
    {
        [$admin, $workspace] = $this->createWorkspace();
        $vault = Vault::factory()->create([
            'workspace_id' => $workspace->getKey(),
            'name' => 'Finance',
            'normalized_name' => 'finance',
        ]);

        $this->actingAs($admin)
            ->delete(route('vaults.destroy', $vault), ['confirmation' => 'finance'])
            ->assertSessionHasErrors('confirmation');

        $this->assertDatabaseHas('vaults', ['id' => $vault->getKey()]);
    }

    public function test_cross_workspace_and_member_mutations_are_forbidden(): void
    {
        [$firstAdmin] = $this->createWorkspace('First');
        [$secondAdmin, $secondWorkspace] = $this->createWorkspace('Second');
        $secondVault = $secondWorkspace->vaults()->firstOrFail();

        $this->actingAs($firstAdmin)
            ->patch(route('vaults.update', $secondVault), ['name' => 'Stolen'])
            ->assertForbidden();

        $secondAdmin->forceFill(['role' => WorkspaceRole::Member])->save();

        $this->actingAs($secondAdmin)
            ->post(route('vaults.store'), ['name' => 'Member vault'])
            ->assertForbidden();

        $this->actingAs($secondAdmin)
            ->get(route('dashboard'))
            ->assertViewHas('vaults', fn ($vaults): bool => $vaults->isEmpty());
    }

    public function test_audit_events_are_name_free_and_immutable(): void
    {
        [$admin] = $this->createWorkspace('Secret Workspace');

        $this->actingAs($admin)
            ->post(route('vaults.store'), ['name' => 'Secret Vault']);

        $event = AuditEvent::query()
            ->where('action', AuditAction::VaultCreated)
            ->firstOrFail();
        $payload = json_encode($event->getAttributes(), JSON_THROW_ON_ERROR);

        $this->assertStringNotContainsString('Secret Vault', $payload);
        $this->assertStringNotContainsString('Secret Workspace', $payload);

        $this->expectException(LogicException::class);
        $event->update(['action' => AuditAction::VaultRenamed]);
    }

    /**
     * @return array{User, Workspace}
     */
    private function createWorkspace(string $name = 'Acme'): array
    {
        $user = User::factory()->create();
        $workspace = app(WorkspaceService::class)->create($user, $name);

        return [$user->fresh(), $workspace->fresh()];
    }
}
