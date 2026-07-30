<?php

namespace App\Notifications;

use App\Models\EmailLoginCode;
use App\Services\EmailLoginCodeService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Throwable;

class EmailLoginCodeNotification extends Notification implements ShouldBeEncrypted, ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(
        public readonly string $email,
        public readonly string $generation,
        public readonly string $code,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function shouldSend(object $notifiable, string $channel): bool
    {
        return EmailLoginCode::query()
            ->where('email', $this->email)
            ->where('generation', $this->generation)
            ->where('expires_at', '>', now())
            ->exists();
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your Shared Authenticator sign-in code')
            ->greeting('Sign in to Shared Authenticator')
            ->line('Enter this code to finish signing in:')
            ->line($this->code)
            ->line('This code expires in '.EmailLoginCodeService::CODE_TTL_MINUTES.' minutes.')
            ->line('If you did not request this code, you can ignore this email.');
    }

    public function afterSending(object $notifiable, string $channel, mixed $response): void
    {
        EmailLoginCode::query()
            ->where('email', $this->email)
            ->where('generation', $this->generation)
            ->update([
                'sent_at' => now(),
                'delivery_failed_at' => null,
            ]);
    }

    public function failed(Throwable $exception): void
    {
        EmailLoginCode::query()
            ->where('email', $this->email)
            ->where('generation', $this->generation)
            ->update(['delivery_failed_at' => now()]);
    }
}
