<?php

namespace Tests\Feature;

use App\Data\AuthenticatorData;
use App\Enums\AuditAction;
use App\Enums\TotpAlgorithm;
use App\Enums\TotpDigits;
use App\Enums\WorkspaceRole;
use App\Exceptions\VaultEncryptionException;
use App\Models\Authenticator;
use App\Models\User;
use App\Models\Vault;
use App\Models\Workspace;
use App\Services\Authenticators\AuthenticatorCipher;
use App\Services\Authenticators\AuthenticatorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticatorManagementTest extends TestCase
{
    use RefreshDatabase;

    private const SECRET = 'JBSWY3DPEHPK3PXP';

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('authenticators.encryption_key', 'base64:'.base64_encode(str_repeat('K', 32)));
    }

    public function test_admin_can_save_and_list_an_encrypted_authenticator_without_exposing_the_seed(): void
    {
        [$admin, $vault] = $this->workspace();

        $response = $this->actingAs($admin)->post(route('vaults.authenticators.store', $vault), $this->payload());

        $authenticator = Authenticator::query()->firstOrFail();
        $response->assertRedirect(route('vaults.authenticators.index', $vault));
        $this->assertStringNotContainsString(self::SECRET, $authenticator->encrypted_payload);
        $this->assertStringNotContainsString('Example', $authenticator->encrypted_payload);
        $this->assertDatabaseHas('vault_encryption_keys', ['vault_id' => $vault->id]);
        $this->assertDatabaseHas('audit_events', ['action' => AuditAction::AuthenticatorCreated->value]);

        $feed = $this->actingAs($admin)->getJson(route('api.vaults.authenticators.index', $vault));
        $feed->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertJsonPath('data.0.issuer', 'Example')
            ->assertJsonMissing(['secret' => self::SECRET]);
        $this->assertStringNotContainsString(self::SECRET, $feed->getContent());
    }

    public function test_reveal_and_copy_return_functional_codes_without_cache_and_record_audit_events(): void
    {
        [$admin, $vault] = $this->workspace();
        $authenticator = app(AuthenticatorService::class)->create($admin, $vault, $this->data());

        $reveal = $this->actingAs($admin)->postJson(route('api.authenticators.reveal', $authenticator));
        $copy = $this->actingAs($admin)->postJson(route('api.authenticators.copy', $authenticator));

        $reveal->assertOk()->assertHeader('Cache-Control', 'no-store, private');
        $copy->assertOk()->assertHeader('Cache-Control', 'no-store, private');
        $this->assertMatchesRegularExpression('/^\d{6}$/', $reveal->json('data.code'));
        $this->assertSame($reveal->json('data.code'), $copy->json('data.code'));
        $this->assertDatabaseHas('audit_events', ['action' => AuditAction::AuthenticatorRevealed->value]);
        $this->assertDatabaseHas('audit_events', ['action' => AuditAction::AuthenticatorCopied->value]);
        $this->assertStringNotContainsString($reveal->json('data.code'), json_encode($admin->workspace->auditEvents()->get()->toArray()));
    }

    public function test_duplicates_require_confirmation_but_can_be_saved(): void
    {
        [$admin, $vault] = $this->workspace();
        app(AuthenticatorService::class)->create($admin, $vault, $this->data());

        $response = $this->actingAs($admin)->post(route('vaults.authenticators.store', $vault), $this->payload());
        $response->assertSessionHasErrors('duplicate');
        $this->assertStringNotContainsString(self::SECRET, serialize(session()->all()));
        $this->assertSame(1, Authenticator::query()->count());

        $this->actingAs($admin)->post(route('vaults.authenticators.store', $vault), [
            ...$this->payload(), 'confirm_duplicate' => true,
        ])->assertRedirect();
        $this->assertSame(2, Authenticator::query()->count());
    }

    public function test_admin_can_edit_move_archive_restore_and_delete_an_authenticator(): void
    {
        [$admin, $source] = $this->workspace();
        $target = Vault::factory()->create(['workspace_id' => $source->workspace_id, 'name' => 'Finance', 'normalized_name' => 'finance']);
        $authenticator = app(AuthenticatorService::class)->create($admin, $source, $this->data());

        $this->actingAs($admin)->patch(route('authenticators.update', $authenticator), [
            'vault_id' => $target->id, 'issuer' => 'Updated', 'account' => 'team@example.com',
        ])->assertRedirect(route('vaults.authenticators.index', $target));

        $authenticator->refresh();
        $decrypted = app(AuthenticatorCipher::class)->decrypt($authenticator);
        $this->assertSame($target->id, $authenticator->vault_id);
        $this->assertSame('Updated', $decrypted->issuer);
        $this->assertSame(self::SECRET, $decrypted->secret);

        $this->actingAs($admin)->post(route('authenticators.archive', $authenticator))->assertRedirect();
        $this->assertNotNull($authenticator->fresh()->archived_at);
        $this->actingAs($admin)->delete(route('authenticators.restore', $authenticator))->assertRedirect();
        $this->assertNull($authenticator->fresh()->archived_at);
        $this->actingAs($admin)->post(route('authenticators.archive', $authenticator));
        $this->actingAs($admin)->delete(route('authenticators.destroy', $authenticator), ['confirmation' => 'Updated: team@example.com'])->assertRedirect();
        $this->assertDatabaseMissing('authenticators', ['id' => $authenticator->id]);
    }

    public function test_secret_replacement_requires_confirmation_and_is_audited(): void
    {
        [$admin, $vault] = $this->workspace();
        $authenticator = app(AuthenticatorService::class)->create($admin, $vault, $this->data());
        $replacement = 'KRUGS4ZANFZSAYJA';
        $payload = [
            'vault_id' => $vault->id,
            'issuer' => 'Example',
            'account' => 'alice@example.com',
            'replacement_secret' => $replacement,
            'replacement_algorithm' => 'SHA256',
            'replacement_digits' => 8,
            'replacement_period' => 45,
        ];

        $this->actingAs($admin)->patch(route('authenticators.update', $authenticator), $payload)
            ->assertSessionHasErrors('confirm_sensitive_change');
        $this->assertSame(self::SECRET, app(AuthenticatorCipher::class)->decrypt($authenticator->fresh())->secret);

        $this->actingAs($admin)->patch(route('authenticators.update', $authenticator), [
            ...$payload, 'confirm_sensitive_change' => true,
        ])->assertRedirect();
        $this->assertSame($replacement, app(AuthenticatorCipher::class)->decrypt($authenticator->fresh())->secret);
        $this->assertDatabaseHas('audit_events', ['action' => AuditAction::AuthenticatorSecretReplaced->value]);
    }

    public function test_cross_workspace_and_member_access_are_denied(): void
    {
        [$admin, $vault] = $this->workspace();
        $authenticator = app(AuthenticatorService::class)->create($admin, $vault, $this->data());
        [$otherAdmin] = $this->workspace('Other');
        $member = User::factory()->create(['workspace_id' => $vault->workspace_id, 'role' => WorkspaceRole::Member]);

        $this->actingAs($otherAdmin)->postJson(route('api.authenticators.reveal', $authenticator))->assertForbidden();
        $this->actingAs($member)->postJson(route('api.authenticators.reveal', $authenticator))->assertForbidden();
    }

    public function test_archived_authenticators_cannot_generate_codes(): void
    {
        [$admin, $vault] = $this->workspace();
        $authenticator = app(AuthenticatorService::class)->create($admin, $vault, $this->data());
        app(AuthenticatorService::class)->archive($admin, $authenticator);

        $this->actingAs($admin)->postJson(route('api.authenticators.reveal', $authenticator))->assertForbidden();
    }

    public function test_code_endpoint_is_rate_limited_per_authenticator(): void
    {
        [$admin, $vault] = $this->workspace();
        $authenticator = app(AuthenticatorService::class)->create($admin, $vault, $this->data());
        $this->actingAs($admin);

        foreach (range(1, 20) as $attempt) {
            $this->postJson(route('api.authenticators.reveal', $authenticator))->assertOk();
        }

        $this->postJson(route('api.authenticators.reveal', $authenticator))->assertTooManyRequests();
    }

    public function test_tampering_and_missing_key_fail_closed(): void
    {
        [$admin, $vault] = $this->workspace();
        $authenticator = app(AuthenticatorService::class)->create($admin, $vault, $this->data());
        $authenticator->update(['encrypted_payload' => base64_encode('tampered')]);

        $this->expectException(VaultEncryptionException::class);
        app(AuthenticatorCipher::class)->decrypt($authenticator->fresh());
    }

    public function test_missing_encryption_configuration_prevents_storage(): void
    {
        [$admin, $vault] = $this->workspace();
        config()->set('authenticators.encryption_key');

        $this->expectException(VaultEncryptionException::class);
        app(AuthenticatorService::class)->create($admin, $vault, $this->data());
    }

    /** @return array{User, Vault} */
    private function workspace(string $name = 'Team'): array
    {
        $admin = User::factory()->create();
        $workspace = Workspace::factory()->create(['name' => $name, 'created_by_user_id' => $admin->id]);
        $admin->forceFill(['workspace_id' => $workspace->id, 'role' => WorkspaceRole::Admin])->save();
        $vault = Vault::factory()->create(['workspace_id' => $workspace->id, 'name' => 'General', 'normalized_name' => 'general']);

        return [$admin->fresh(), $vault];
    }

    /** @return array<string, mixed> */
    private function payload(): array
    {
        return ['issuer' => 'Example', 'account' => 'alice@example.com', 'secret' => self::SECRET, 'algorithm' => 'SHA1', 'digits' => 6, 'period' => 30];
    }

    private function data(): AuthenticatorData
    {
        return new AuthenticatorData('Example', 'alice@example.com', self::SECRET, TotpAlgorithm::Sha1, TotpDigits::Six, 30);
    }
}
