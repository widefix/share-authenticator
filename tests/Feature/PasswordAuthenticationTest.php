<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class PasswordAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_public_registration_is_unavailable(): void
    {
        $this->get('/register')->assertNotFound();
        $this->post('/register', ['email' => 'bot@example.com', 'password' => 'long-password'])->assertNotFound();
        $this->get(route('login'))->assertDontSee('Create an account');
        $this->assertDatabaseCount('users', 0);
    }

    public function test_registered_user_can_sign_in_with_email_and_password(): void
    {
        $user = User::factory()->create([
            'email' => 'member@example.com',
            'password' => 'correct-horse-battery',
        ]);

        $this->post(route('login.store'), [
            'email' => ' MEMBER@example.com ',
            'password' => 'correct-horse-battery',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_invalid_credentials_are_rejected_with_a_neutral_error(): void
    {
        User::factory()->create([
            'email' => 'member@example.com',
            'password' => 'correct-horse-battery',
        ]);

        $this->from(route('login'))->post(route('login.store'), [
            'email' => 'member@example.com',
            'password' => 'incorrect-password',
        ])->assertRedirect(route('login'))->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_legacy_user_without_password_cannot_sign_in(): void
    {
        User::factory()->create([
            'email' => 'legacy@example.com',
            'password' => null,
        ]);

        $this->post(route('login.store'), [
            'email' => 'legacy@example.com',
            'password' => 'any-password-value',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_login_is_rate_limited_after_five_attempts(): void
    {
        User::factory()->create([
            'email' => 'limited@example.com',
            'password' => 'correct-horse-battery',
        ]);

        foreach (range(1, 5) as $attempt) {
            $this->post(route('login.store'), [
                'email' => 'limited@example.com',
                'password' => 'wrong-password',
            ])->assertSessionHasErrors('email');
        }

        $this->post(route('login.store'), [
            'email' => 'limited@example.com',
            'password' => 'correct-horse-battery',
        ])->assertHeader('Retry-After')->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_rotating_emails_cannot_bypass_ip_limit(): void
    {
        foreach (range(1, 20) as $attempt) {
            $this->post(route('login.store'), [
                'email' => "bot{$attempt}@example.com", 'password' => 'wrong-password',
            ])->assertHeaderMissing('Retry-After');
        }
        $this->post(route('login.store'), [
            'email' => 'another@example.com', 'password' => 'wrong-password',
        ])->assertHeader('Retry-After');
    }

    public function test_rotating_ips_cannot_bypass_account_limit_and_limit_expires(): void
    {
        foreach (range(1, 30) as $attempt) {
            $this->withServerVariables(['REMOTE_ADDR' => "192.0.2.{$attempt}"])
                ->post(route('login.store'), [
                    'email' => 'target@example.com', 'password' => 'wrong-password',
                ])->assertHeaderMissing('Retry-After');
        }
        $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.31'])
            ->post(route('login.store'), [
                'email' => ' TARGET@example.com ', 'password' => 'wrong-password',
            ])->assertHeader('Retry-After');
        $this->travel(61)->minutes();
        $this->post(route('login.store'), [
            'email' => 'target@example.com', 'password' => 'wrong-password',
        ])->assertHeaderMissing('Retry-After');
    }

    public function test_guest_and_authenticated_route_boundaries(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));

        $user = User::factory()->create();

        $this->actingAs($user)->get(route('login'))->assertRedirect(route('dashboard'));
        $this->get(route('dashboard'))->assertOk()->assertSee($user->email);
    }

    public function test_https_is_detected_behind_the_deployment_proxy(): void
    {
        $response = $this
            ->withHeaders([
                'X-Forwarded-Host' => 'authenticator.example.com',
                'X-Forwarded-Proto' => 'https',
            ])
            ->get(route('login'));

        $response
            ->assertOk()
            ->assertSee('https://authenticator.example.com/build/assets/', false)
            ->assertDontSee('http://authenticator.example.com/build/assets/', false);
    }

    public function test_user_can_log_out(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('logout'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }
}
