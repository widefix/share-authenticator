<?php

namespace Tests\Feature;

use App\Models\EmailLoginCode;
use App\Models\User;
use App\Notifications\EmailLoginCodeNotification;
use App\Services\Auth\EmailLoginCodeService;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class EmailOtpAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    public function test_requesting_a_code_normalizes_email_without_creating_a_user(): void
    {
        Notification::fake();

        $response = $this->post(route('login.store'), [
            'email' => '  Person@Example.COM  ',
        ]);

        $response->assertRedirect(route('login.verify'))
            ->assertSessionHas('login.pending_email', 'person@example.com')
            ->assertSessionHas('login.pending_generation');

        $this->assertDatabaseHas('email_login_codes', ['email' => 'person@example.com']);
        $this->assertDatabaseMissing('users', ['email' => 'person@example.com']);

        Notification::assertSentOnDemand(
            EmailLoginCodeNotification::class,
            fn (EmailLoginCodeNotification $notification, array $channels, object $notifiable): bool => $notifiable->routes['mail'] === 'person@example.com'
                && $channels === ['mail'],
        );
    }

    public function test_invalid_email_is_rejected_without_creating_a_challenge(): void
    {
        Notification::fake();

        $this->post(route('login.store'), ['email' => 'not-an-email'])
            ->assertSessionHasErrors('email');

        $this->assertSame(0, EmailLoginCode::query()->count());
        Notification::assertNothingSent();
    }

    public function test_plaintext_code_is_not_stored_in_the_challenge(): void
    {
        Notification::fake();

        $this->post(route('login.store'), ['email' => 'secure@example.com']);

        $notification = $this->sentLoginNotification();
        $challenge = EmailLoginCode::query()->findOrFail('secure@example.com');

        $this->assertNotSame($notification->code, $challenge->code_hash);
        $this->assertStringNotContainsString($notification->code, $challenge->code_hash);
    }

    public function test_queued_notification_payload_is_encrypted(): void
    {
        config(['queue.default' => 'database']);

        $notification = new EmailLoginCodeNotification(
            'queue@example.com',
            'fe142c9a-79b3-45ba-a246-cb21732aaab5',
            '123456',
        );

        $this->assertInstanceOf(ShouldQueue::class, $notification);
        $this->assertInstanceOf(ShouldBeEncrypted::class, $notification);

        Notification::route('mail', 'queue@example.com')->notify($notification);

        $payload = (string) DB::table('jobs')->value('payload');

        $this->assertNotSame('', $payload);
        $this->assertStringNotContainsString('123456', $payload);
        $this->assertStringNotContainsString('queue@example.com', $payload);
    }

    public function test_valid_code_creates_and_authenticates_a_verified_user(): void
    {
        Notification::fake();

        $this->post(route('login.store'), ['email' => 'new@example.com']);
        $notification = $this->sentLoginNotification();

        $response = $this->post(route('login.authenticate'), [
            'code' => $notification->code,
        ]);

        $response->assertRedirect(route('dashboard'))
            ->assertSessionMissing('login.pending_email')
            ->assertSessionMissing('login.pending_generation');

        $user = User::query()->where('email', 'new@example.com')->firstOrFail();

        $this->assertNotNull($user->email_verified_at);
        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseMissing('email_login_codes', ['email' => 'new@example.com']);
    }

    public function test_existing_user_logs_in_without_creating_a_duplicate(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email' => 'member@example.com']);

        $this->post(route('login.store'), ['email' => 'MEMBER@example.com']);
        $notification = $this->sentLoginNotification();

        $this->post(route('login.authenticate'), ['code' => $notification->code])
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
        $this->assertSame(1, User::query()->where('email', 'member@example.com')->count());
    }

    public function test_invalid_code_increments_attempts(): void
    {
        Notification::fake();

        $this->post(route('login.store'), ['email' => 'attempts@example.com']);
        $notification = $this->sentLoginNotification();

        $this->from(route('login.verify'))
            ->post(route('login.authenticate'), ['code' => $this->incorrectCodeFor($notification)])
            ->assertRedirect(route('login.verify'))
            ->assertSessionHasErrors('code');

        $this->assertDatabaseHas('email_login_codes', [
            'email' => 'attempts@example.com',
            'attempts' => 1,
        ]);
        $this->assertGuest();
    }

    public function test_five_invalid_attempts_invalidate_the_challenge(): void
    {
        Notification::fake();

        $this->post(route('login.store'), ['email' => 'locked@example.com']);
        $notification = $this->sentLoginNotification();
        $incorrectCode = $this->incorrectCodeFor($notification);

        foreach (range(1, 4) as $attempt) {
            $this->from(route('login.verify'))
                ->post(route('login.authenticate'), ['code' => $incorrectCode])
                ->assertRedirect(route('login.verify'));
        }

        $this->post(route('login.authenticate'), ['code' => $incorrectCode])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $this->assertDatabaseMissing('email_login_codes', ['email' => 'locked@example.com']);
        $this->assertGuest();
    }

    public function test_verification_middleware_limits_malformed_attempts(): void
    {
        Notification::fake();

        $this->post(route('login.store'), ['email' => 'malformed@example.com']);

        foreach (range(1, 5) as $attempt) {
            $this->from(route('login.verify'))
                ->post(route('login.authenticate'), ['code' => 'invalid'])
                ->assertRedirect(route('login.verify'))
                ->assertSessionHasErrors('code');
        }

        $this->post(route('login.authenticate'), ['code' => 'invalid'])
            ->assertRedirect(route('login'))
            ->assertHeader('Retry-After')
            ->assertSessionHasErrors('email');

        $this->assertDatabaseMissing('email_login_codes', [
            'email' => 'malformed@example.com',
        ]);
    }

    public function test_expired_code_is_rejected_and_deleted(): void
    {
        Notification::fake();

        $this->post(route('login.store'), ['email' => 'expired@example.com']);
        $notification = $this->sentLoginNotification();

        EmailLoginCode::query()
            ->where('email', 'expired@example.com')
            ->update(['expires_at' => now()->subSecond()]);

        $this->post(route('login.authenticate'), ['code' => $notification->code])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $this->assertDatabaseMissing('email_login_codes', ['email' => 'expired@example.com']);
        $this->assertDatabaseMissing('users', ['email' => 'expired@example.com']);
    }

    public function test_requesting_another_code_immediately_is_rate_limited(): void
    {
        Notification::fake();

        $this->post(route('login.store'), ['email' => 'cooldown@example.com'])
            ->assertRedirect(route('login.verify'));

        $generation = EmailLoginCode::query()->findOrFail('cooldown@example.com')->generation;

        $this->post(route('login.resend'))
            ->assertRedirect(route('login.verify'))
            ->assertHeader('Retry-After')
            ->assertSessionHasErrors('resend');

        $this->assertSame(
            $generation,
            EmailLoginCode::query()->findOrFail('cooldown@example.com')->generation,
        );
    }

    public function test_verification_screen_renders_the_accessible_code_form(): void
    {
        Notification::fake();

        $this->post(route('login.store'), ['email' => 'person@example.com']);

        $this->get(route('login.verify'))
            ->assertOk()
            ->assertSee('p••••n@example.com')
            ->assertSee('autocomplete="one-time-code"', escape: false)
            ->assertSee('inputmode="numeric"', escape: false);
    }

    public function test_three_send_limit_is_enforced_across_resends(): void
    {
        Notification::fake();

        $this->post(route('login.store'), ['email' => 'limited@example.com']);

        foreach (range(1, 2) as $resend) {
            $this->travel(61)->seconds();
            $this->post(route('login.resend'))->assertRedirect(route('login.verify'));
        }

        $generation = EmailLoginCode::query()->findOrFail('limited@example.com')->generation;

        $this->travel(61)->seconds();
        $this->post(route('login.resend'))
            ->assertRedirect(route('login.verify'))
            ->assertSessionHasErrors('resend');

        $this->assertSame(
            $generation,
            EmailLoginCode::query()->findOrFail('limited@example.com')->generation,
        );
    }

    public function test_twenty_send_per_ip_limit_is_enforced(): void
    {
        Notification::fake();

        foreach (range(1, 20) as $index) {
            $this->post(route('login.store'), [
                'email' => "member{$index}@example.com",
            ])->assertRedirect(route('login.verify'));
        }

        $this->post(route('login.store'), ['email' => 'blocked@example.com'])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $this->assertDatabaseMissing('email_login_codes', ['email' => 'blocked@example.com']);
    }

    public function test_new_generation_supersedes_an_older_notification(): void
    {
        Notification::fake();
        $service = app(EmailLoginCodeService::class);

        $first = $service->issue('latest@example.com');
        $firstNotification = $this->sentLoginNotification();
        $second = $service->issue('latest@example.com');

        $this->assertNotSame($first->generation, $second->generation);
        $this->assertFalse($firstNotification->shouldSend(new AnonymousNotifiable, 'mail'));
    }

    public function test_verification_requires_a_pending_session_challenge(): void
    {
        $this->get(route('login.verify'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $this->post(route('login.authenticate'), ['code' => '123456'])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');
    }

    public function test_guest_and_authenticated_route_boundaries(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));

        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('login'))
            ->assertRedirect(route('dashboard'));

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee($user->email);
    }

    public function test_user_can_log_out(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('logout'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    private function sentLoginNotification(): EmailLoginCodeNotification
    {
        $sent = null;

        Notification::assertSentOnDemand(
            EmailLoginCodeNotification::class,
            function (EmailLoginCodeNotification $notification) use (&$sent): bool {
                $sent = $notification;

                return true;
            },
        );

        $this->assertInstanceOf(EmailLoginCodeNotification::class, $sent);

        return $sent;
    }

    private function incorrectCodeFor(EmailLoginCodeNotification $notification): string
    {
        return $notification->code === '999999' ? '000000' : '999999';
    }
}
