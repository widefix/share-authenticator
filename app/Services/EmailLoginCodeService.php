<?php

namespace App\Services;

use App\Data\IssuedLoginCode;
use App\Data\LoginCodeVerification;
use App\Enums\LoginCodeVerificationStatus;
use App\Models\EmailLoginCode;
use App\Models\User;
use App\Notifications\EmailLoginCodeNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

class EmailLoginCodeService
{
    public const CODE_TTL_MINUTES = 10;

    public const MAX_ATTEMPTS = 5;

    public function issue(string $email): IssuedLoginCode
    {
        $email = $this->normalizeEmail($email);
        $generation = (string) Str::uuid();
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        DB::transaction(function () use ($email, $generation, $code): void {
            EmailLoginCode::query()->updateOrCreate(
                ['email' => $email],
                [
                    'generation' => $generation,
                    'code_hash' => Hash::make($this->pepperedCode($email, $generation, $code)),
                    'attempts' => 0,
                    'expires_at' => now()->addMinutes(self::CODE_TTL_MINUTES),
                    'sent_at' => null,
                    'delivery_failed_at' => null,
                ],
            );
        });

        Notification::route('mail', $email)
            ->notify(new EmailLoginCodeNotification($email, $generation, $code));

        return new IssuedLoginCode($email, $generation);
    }

    public function verify(string $email, string $generation, string $code): LoginCodeVerification
    {
        $email = $this->normalizeEmail($email);

        return DB::transaction(function () use ($email, $generation, $code): LoginCodeVerification {
            $challenge = EmailLoginCode::query()
                ->where('email', $email)
                ->where('generation', $generation)
                ->lockForUpdate()
                ->first();

            if (! $challenge) {
                return new LoginCodeVerification(LoginCodeVerificationStatus::Expired);
            }

            if ($challenge->expires_at->isPast()) {
                $challenge->delete();

                return new LoginCodeVerification(LoginCodeVerificationStatus::Expired);
            }

            if ($challenge->attempts >= self::MAX_ATTEMPTS) {
                $challenge->delete();

                return new LoginCodeVerification(LoginCodeVerificationStatus::Locked);
            }

            $valid = Hash::check(
                $this->pepperedCode($email, $generation, $code),
                $challenge->code_hash,
            );

            if (! $valid) {
                $challenge->increment('attempts');

                if ($challenge->attempts >= self::MAX_ATTEMPTS) {
                    $challenge->delete();

                    return new LoginCodeVerification(LoginCodeVerificationStatus::Locked);
                }

                return new LoginCodeVerification(LoginCodeVerificationStatus::Invalid);
            }

            $challenge->delete();

            $user = User::query()->firstOrCreate(['email' => $email]);

            if (! $user->email_verified_at) {
                $user->forceFill(['email_verified_at' => now()])->save();
            }

            return new LoginCodeVerification(LoginCodeVerificationStatus::Valid, $user);
        });
    }

    public function normalizeEmail(string $email): string
    {
        return Str::lower(trim($email));
    }

    private function pepperedCode(string $email, string $generation, string $code): string
    {
        return hash_hmac(
            'sha256',
            implode('|', [$email, $generation, $code]),
            (string) config('app.key'),
        );
    }
}
