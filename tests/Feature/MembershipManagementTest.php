<?php

namespace Tests\Feature;

use App\Data\AuthenticatorData;
use App\Enums\AuditAction;
use App\Enums\RotationReason;
use App\Enums\TotpAlgorithm;
use App\Enums\TotpDigits;
use App\Enums\WorkspaceRole;
use App\Models\Authenticator;
use App\Models\User;
use App\Models\Vault;
use App\Models\Workspace;
use App\Models\WorkspaceInvitation;
use App\Services\Authenticators\AuthenticatorService;
use App\Services\Membership\InvitationService;
use App\Services\Workspaces\WorkspaceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MembershipManagementTest extends TestCase
{
    use RefreshDatabase;

    private int $secretIndex = 0;

    protected function setUp(): void
    {
        parent::setUp();
        config(['authenticators.encryption_key' => base64_encode(random_bytes(32))]);
    }

    public function test_admin_can_invite_a_member_without_creating_a_user(): void
    {
        [$admin, $workspace, $vault] = $this->workspace();

        $response = $this->actingAs($admin)->post(route('invitations.store'), [
            'email' => '  TEAMMATE@Example.com ',
            'role' => WorkspaceRole::Member->value,
            'vault_ids' => [$vault->id],
        ]);
        $response->assertRedirect(route('members.index'))->assertSessionHas('invitation_link');

        $invitation = WorkspaceInvitation::query()->firstOrFail();
        $this->assertSame('teammate@example.com', $invitation->email);
        $this->assertSame(WorkspaceRole::Member, $invitation->role);
        $this->assertTrue($invitation->vaults->contains($vault));
        $this->assertSame(1, User::query()->count());
        $this->assertDatabaseHas('audit_events', [
            'action' => AuditAction::InvitationCreated->value,
            'subject_id' => $invitation->id,
        ]);

        $link = (string) session('invitation_link');
        $token = basename($link);
        $this->assertSame(hash('sha256', $token), $invitation->token_hash);
        $this->assertDatabaseCount('jobs', 0);
    }

    public function test_invitation_is_bound_to_email_token_and_single_use(): void
    {
        [$admin, $workspace, $vault] = $this->workspace();
        [$invitation, $token] = $this->invite($admin, 'member@example.com', [$vault->id]);
        $member = User::factory()->create(['email' => 'member@example.com']);
        $wrongUser = User::factory()->create(['email' => 'wrong@example.com']);

        $this->actingAs($wrongUser)
            ->post(route('invitations.accept', [$invitation, $token]))
            ->assertForbidden();
        $this->actingAs($member)
            ->post(route('invitations.accept', [$invitation, $token.'changed']))
            ->assertForbidden();

        $this->actingAs($member)
            ->post(route('invitations.accept', [$invitation, $token]))
            ->assertRedirect(route('dashboard'));

        $member->refresh();
        $this->assertSame($workspace->id, $member->workspace_id);
        $this->assertSame(WorkspaceRole::Member, $member->role);
        $this->assertTrue($member->vaults->contains($vault));
        $this->assertNotNull($invitation->fresh()->accepted_at);

        $this->actingAs($member)
            ->post(route('invitations.accept', [$invitation, $token]))
            ->assertForbidden();
    }

    public function test_new_user_can_register_directly_from_an_invitation_link(): void
    {
        [$admin, $workspace, $vault] = $this->workspace();
        [$invitation, $token] = $this->invite($admin, 'new-member@example.com', [$vault->id]);

        $this->get(route('invitations.show', [$invitation, $token]))
            ->assertOk()
            ->assertSee('Create account and join');

        $this->post(route('invitations.register', [$invitation, $token]), [
            'password' => 'invitation-password',
            'password_confirmation' => 'invitation-password',
        ])->assertRedirect(route('dashboard'));

        $member = User::query()->where('email', 'new-member@example.com')->firstOrFail();
        $this->assertAuthenticatedAs($member);
        $this->assertTrue(Hash::check('invitation-password', $member->password));
        $this->assertSame($workspace->id, $member->workspace_id);
        $this->assertTrue($member->vaults->contains($vault));
        $this->assertNotNull($invitation->fresh()->accepted_at);
    }

    public function test_existing_user_signs_in_then_returns_to_the_invitation(): void
    {
        [$admin, $workspace, $vault] = $this->workspace();
        [$invitation, $token] = $this->invite($admin, 'existing-login@example.com', [$vault->id]);
        $user = User::factory()->create([
            'email' => 'existing-login@example.com',
            'password' => 'existing-password',
        ]);
        $invitationUrl = route('invitations.show', [$invitation, $token]);

        $this->get($invitationUrl)
            ->assertRedirect(route('login'))
            ->assertSessionHas('url.intended', $invitationUrl);

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'existing-password',
        ])->assertRedirect($invitationUrl);

        $this->get($invitationUrl)->assertOk()->assertSee('Accept invitation');
        $this->post(route('invitations.accept', [$invitation, $token]))
            ->assertRedirect(route('dashboard'));

        $this->assertSame($workspace->id, $user->fresh()->workspace_id);
    }

    public function test_existing_member_can_accept_an_invitation_and_move_workspaces(): void
    {
        [$oldAdmin, $oldWorkspace, $oldVault] = $this->workspace();
        $oldAuthenticator = $this->authenticator($oldAdmin, $oldVault, 'Old workspace');
        $existingUser = User::factory()->create([
            'email' => 'existing@example.com',
            'workspace_id' => $oldWorkspace->id,
            'role' => WorkspaceRole::Member,
        ]);
        $existingUser->vaults()->attach($oldVault, ['granted_by_user_id' => $oldAdmin->id]);

        [$newAdmin, $newWorkspace, $newVault] = $this->workspace();
        [$invitation, $token] = $this->invite($newAdmin, $existingUser->email, [$newVault->id]);

        $this->actingAs($existingUser)
            ->post(route('invitations.accept', [$invitation, $token]))
            ->assertRedirect(route('dashboard'));

        $existingUser->refresh();
        $this->assertSame($newWorkspace->id, $existingUser->workspace_id);
        $this->assertSame(WorkspaceRole::Member, $existingUser->role);
        $this->assertTrue($existingUser->vaults->contains($newVault));
        $this->assertFalse($existingUser->vaults->contains($oldVault));
        $this->assertDatabaseHas('rotation_tasks', [
            'workspace_id' => $oldWorkspace->id,
            'authenticator_id' => $oldAuthenticator->id,
            'affected_user_id' => $existingUser->id,
            'reason' => RotationReason::MemberRemoved->value,
        ]);
    }

    public function test_existing_last_admin_can_be_invited_but_cannot_abandon_their_workspace(): void
    {
        [$lastAdmin, $oldWorkspace] = $this->workspace();
        $lastAdmin->forceFill(['email' => 'last-admin@example.com'])->save();
        [$newAdmin, $newWorkspace, $newVault] = $this->workspace();
        [$invitation, $token] = $this->invite($newAdmin, $lastAdmin->email, [$newVault->id]);

        $this->actingAs($lastAdmin)
            ->post(route('invitations.accept', [$invitation, $token]))
            ->assertSessionHasErrors('invitation');

        $this->assertSame($oldWorkspace->id, $lastAdmin->fresh()->workspace_id);
        $this->assertNull($invitation->fresh()->accepted_at);
    }

    public function test_expired_and_revoked_invitations_are_denied_and_audited(): void
    {
        [$admin, $workspace, $vault] = $this->workspace();
        [$expired, $expiredToken] = $this->invite($admin, 'expired@example.com', [$vault->id]);
        $expired->update(['expires_at' => now()->subMinute()]);
        $expiredUser = User::factory()->create(['email' => 'expired@example.com']);

        $this->actingAs($expiredUser)
            ->post(route('invitations.accept', [$expired, $expiredToken]))
            ->assertSessionHasErrors('invitation');
        $this->assertNotNull($expired->fresh()->expired_at);
        $this->assertDatabaseHas('audit_events', [
            'action' => AuditAction::InvitationExpired->value,
            'subject_id' => $expired->id,
        ]);

        [$revoked, $revokedToken] = $this->invite($admin, 'revoked@example.com', [$vault->id]);
        $this->actingAs($admin)
            ->post(route('invitations.revoke', $revoked))
            ->assertRedirect(route('members.index'));
        $revokedUser = User::factory()->create(['email' => 'revoked@example.com']);
        $this->actingAs($revokedUser)
            ->post(route('invitations.accept', [$revoked, $revokedToken]))
            ->assertForbidden();
        $this->assertDatabaseHas('audit_events', [
            'action' => AuditAction::InvitationRevoked->value,
            'subject_id' => $revoked->id,
        ]);
    }

    public function test_member_can_only_use_authenticators_in_assigned_vaults(): void
    {
        [$admin, $workspace, $assigned] = $this->workspace();
        $unassigned = Vault::factory()->create(['workspace_id' => $workspace->id]);
        $assignedAuthenticator = $this->authenticator($admin, $assigned, 'Assigned');
        $unassignedAuthenticator = $this->authenticator($admin, $unassigned, 'Private');
        $member = User::factory()->create([
            'workspace_id' => $workspace->id,
            'role' => WorkspaceRole::Member,
        ]);
        $member->vaults()->attach($assigned, ['granted_by_user_id' => $admin->id]);

        $this->actingAs($member)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee($assigned->name)
            ->assertDontSee($unassigned->name);
        $this->actingAs($member)
            ->getJson(route('api.vaults.authenticators.index', $assigned))
            ->assertOk()
            ->assertJsonPath('data.0.issuer', 'Assigned');
        $this->actingAs($member)
            ->postJson(route('api.authenticators.reveal', $assignedAuthenticator))
            ->assertOk()
            ->assertJsonStructure(['data' => ['code', 'expires_at', 'server_time', 'digits', 'period']]);

        $this->actingAs($member)->getJson(route('api.vaults.authenticators.index', $unassigned))->assertForbidden();
        $this->actingAs($member)->postJson(route('api.authenticators.reveal', $unassignedAuthenticator))->assertForbidden();
        $this->actingAs($member)->get(route('authenticators.edit', $assignedAuthenticator))->assertForbidden();
    }

    public function test_changing_vault_access_revokes_authorization_and_creates_rotation_tasks(): void
    {
        [$admin, $workspace, $keptVault] = $this->workspace();
        $removedVault = Vault::factory()->create(['workspace_id' => $workspace->id]);
        $this->authenticator($admin, $keptVault, 'Kept');
        $removedAuthenticator = $this->authenticator($admin, $removedVault, 'Removed');
        $member = User::factory()->create([
            'workspace_id' => $workspace->id,
            'role' => WorkspaceRole::Member,
        ]);
        $member->vaults()->attach([$keptVault->id, $removedVault->id], ['granted_by_user_id' => $admin->id]);

        $this->actingAs($admin)->patch(route('members.update', $member), [
            'role' => WorkspaceRole::Member->value,
            'vault_ids' => [$keptVault->id],
        ])->assertRedirect(route('members.index'));

        $this->assertTrue($member->fresh()->vaults->contains($keptVault));
        $this->assertFalse($member->fresh()->vaults->contains($removedVault));
        $this->assertDatabaseHas('rotation_tasks', [
            'authenticator_id' => $removedAuthenticator->id,
            'affected_user_id' => $member->id,
            'reason' => RotationReason::VaultAccessRemoved->value,
            'status' => 'open',
        ]);
        $this->actingAs($member)
            ->getJson(route('api.vaults.authenticators.index', $removedVault))
            ->assertForbidden();
    }

    public function test_promotion_grants_all_vaults_and_demotion_requires_explicit_assignments(): void
    {
        [$admin, $workspace, $assignedVault] = $this->workspace();
        $otherVault = Vault::factory()->create(['workspace_id' => $workspace->id]);
        $otherAuthenticator = $this->authenticator($admin, $otherVault, 'Other');
        $member = User::factory()->create([
            'workspace_id' => $workspace->id,
            'role' => WorkspaceRole::Member,
        ]);
        $member->vaults()->attach($assignedVault, ['granted_by_user_id' => $admin->id]);

        $this->actingAs($admin)->patch(route('members.update', $member), [
            'role' => WorkspaceRole::Admin->value,
        ])->assertRedirect(route('members.index'));
        $member->refresh();
        $this->assertSame(WorkspaceRole::Admin, $member->role);
        $this->assertSame(0, $member->vaults()->count());
        $this->actingAs($member)
            ->getJson(route('api.vaults.authenticators.index', $otherVault))
            ->assertOk();

        $this->actingAs($admin)->patch(route('members.update', $member), [
            'role' => WorkspaceRole::Member->value,
            'vault_ids' => [],
        ])->assertSessionHasErrors('vault_ids');
        $this->assertSame(WorkspaceRole::Admin, $member->fresh()->role);

        $this->actingAs($admin)->patch(route('members.update', $member), [
            'role' => WorkspaceRole::Member->value,
            'vault_ids' => [$assignedVault->id],
        ])->assertRedirect(route('members.index'));
        $this->assertSame(WorkspaceRole::Member, $member->fresh()->role);
        $this->assertDatabaseHas('rotation_tasks', [
            'authenticator_id' => $otherAuthenticator->id,
            'affected_user_id' => $member->id,
            'reason' => RotationReason::VaultAccessRemoved->value,
        ]);
    }

    public function test_admin_invitation_grants_workspace_wide_access_without_explicit_grants(): void
    {
        [$admin, $workspace, $vault] = $this->workspace();
        $otherVault = Vault::factory()->create(['workspace_id' => $workspace->id]);
        $issued = app(InvitationService::class)->invite($admin, 'admin2@example.com', WorkspaceRole::Admin, [$vault->id]);
        $invitation = $issued->invitation;
        $token = $issued->token;
        $newAdmin = User::factory()->create(['email' => 'admin2@example.com']);

        $this->actingAs($newAdmin)
            ->post(route('invitations.accept', [$invitation, $token]))
            ->assertRedirect(route('dashboard'));

        $newAdmin->refresh();
        $this->assertSame(WorkspaceRole::Admin, $newAdmin->role);
        $this->assertSame(0, $newAdmin->vaults()->count());
        $this->actingAs($newAdmin)
            ->get(route('vaults.authenticators.index', $otherVault))
            ->assertOk();
    }

    public function test_removing_member_revokes_sessions_grants_and_creates_rotation_tasks(): void
    {
        [$admin, $workspace, $vault] = $this->workspace();
        $authenticator = $this->authenticator($admin, $vault, 'Removed member');
        $member = User::factory()->create([
            'email' => 'remove@example.com',
            'workspace_id' => $workspace->id,
            'role' => WorkspaceRole::Member,
        ]);
        $member->vaults()->attach($vault, ['granted_by_user_id' => $admin->id]);
        DB::table('sessions')->insert([
            'id' => 'member-session',
            'user_id' => $member->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'test',
            'payload' => 'test',
            'last_activity' => now()->timestamp,
        ]);

        $this->actingAs($admin)->delete(route('members.destroy', $member), [
            'confirmation' => 'remove@example.com',
        ])->assertRedirect(route('members.index'));

        $member->refresh();
        $this->assertNull($member->workspace_id);
        $this->assertNull($member->role);
        $this->assertSame(0, $member->vaults()->count());
        $this->assertDatabaseMissing('sessions', ['id' => 'member-session']);
        $this->assertDatabaseHas('rotation_tasks', [
            'authenticator_id' => $authenticator->id,
            'affected_user_id' => $member->id,
            'reason' => RotationReason::MemberRemoved->value,
        ]);
        $this->assertDatabaseHas('audit_events', [
            'action' => AuditAction::MemberRemoved->value,
            'subject_id' => $member->id,
        ]);
    }

    public function test_non_admin_cannot_manage_members_and_last_admin_cannot_remove_self(): void
    {
        [$admin, $workspace, $vault] = $this->workspace();
        $member = User::factory()->create([
            'workspace_id' => $workspace->id,
            'role' => WorkspaceRole::Member,
        ]);

        $this->actingAs($member)->get(route('members.index'))->assertForbidden();
        $this->actingAs($member)->post(route('invitations.store'), [
            'email' => 'new@example.com',
            'role' => 'member',
            'vault_ids' => [$vault->id],
        ])->assertForbidden();
        $this->actingAs($admin)->delete(route('members.destroy', $admin), [
            'confirmation' => $admin->email,
        ])->assertForbidden();
        $this->assertSame(WorkspaceRole::Admin, $admin->fresh()->role);
    }

    /** @return array{User, Workspace, Vault} */
    private function workspace(): array
    {
        $admin = User::factory()->create();
        $workspace = app(WorkspaceService::class)->create($admin, 'Acme');
        $admin->refresh();

        return [$admin, $workspace, $workspace->vaults()->firstOrFail()];
    }

    /** @return array{WorkspaceInvitation, string} */
    private function invite(User $admin, string $email, array $vaultIds): array
    {
        $issued = app(InvitationService::class)->invite($admin, $email, WorkspaceRole::Member, $vaultIds);

        return [$issued->invitation, $issued->token];
    }

    private function authenticator(User $admin, Vault $vault, string $issuer): Authenticator
    {
        $secrets = ['JBSWY3DPEHPK3PXP', 'KRUGS4ZANFZSAYJA', 'MFRGGZDFMZTWQ2LK'];

        return app(AuthenticatorService::class)->create(
            $admin,
            $vault,
            new AuthenticatorData(
                issuer: $issuer,
                account: 'account@example.com',
                secret: $secrets[$this->secretIndex++],
                algorithm: TotpAlgorithm::Sha1,
                digits: TotpDigits::Six,
                period: 30,
            ),
        );
    }
}
