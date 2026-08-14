<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PasswordAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_public_registration_normalizes_email_hashes_password_and_signs_user_in(): void
    {
        $response = $this->post(route('register.store'), [
            'email' => '  Person@Example.COM  ',
            'password' => 'a-secure-password',
            'password_confirmation' => 'a-secure-password',
        ]);

        $response->assertRedirect(route('dashboard'));
        $user = User::query()->where('email', 'person@example.com')->firstOrFail();
        $this->assertAuthenticatedAs($user);
        $this->assertTrue(Hash::check('a-secure-password', $user->password));
        $this->assertNotSame('a-secure-password', $user->password);
    }

    public function test_registration_requires_a_unique_email_and_confirmed_twelve_character_password(): void
    {
        User::factory()->create(['email' => 'existing@example.com']);

        $this->post(route('register.store'), [
            'email' => 'existing@example.com',
            'password' => 'short',
            'password_confirmation' => 'different',
        ])->assertSessionHasErrors(['email', 'password']);

        $this->assertGuest();
        $this->assertSame(1, User::query()->count());
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

    public function test_guest_and_authenticated_route_boundaries(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));

        $user = User::factory()->create();

        $this->actingAs($user)->get(route('login'))->assertRedirect(route('dashboard'));
        $this->actingAs($user)->get(route('register'))->assertRedirect(route('dashboard'));
        $this->get(route('dashboard'))->assertOk()->assertSee($user->email);
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
