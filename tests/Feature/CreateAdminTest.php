<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CreateAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_cli_creates_initial_admin_and_workspace(): void
    {
        $this->artisan('user:create-admin', ['email' => ' ADMIN@example.com ', '--workspace' => 'My team'])
            ->expectsQuestion('Password (at least 12 characters)', 'secure-password')
            ->expectsQuestion('Confirm password', 'secure-password')
            ->assertSuccessful();

        $user = User::query()->sole();
        $this->assertSame('admin@example.com', $user->email);
        $this->assertTrue($user->isWorkspaceAdmin());
        $this->assertTrue(Hash::check('secure-password', $user->password));
        $this->assertSame('My team', $user->workspace->name);
        $this->assertSame(1, $user->workspace->vaults()->count());
    }

    public function test_cli_rejects_invalid_password_without_creating_data(): void
    {
        $this->artisan('user:create-admin', ['email' => 'admin@example.com', '--workspace' => 'My team'])
            ->expectsQuestion('Password (at least 12 characters)', 'short')
            ->expectsQuestion('Confirm password', 'different')
            ->assertFailed();
        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('workspaces', 0);
    }

    public function test_cli_refuses_existing_installation(): void
    {
        User::factory()->create();
        $this->artisan('user:create-admin', ['email' => 'admin@example.com'])->assertFailed();
        $this->assertDatabaseCount('users', 1);
    }
}
