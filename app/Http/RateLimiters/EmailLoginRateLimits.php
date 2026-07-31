<?php

namespace App\Http\RateLimiters;

use App\Http\Responses\Auth\EmailLoginResponseFactory;
use App\Services\Auth\PendingEmailLogin;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Cache\RateLimiting\Unlimited;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final readonly class EmailLoginRateLimits
{
    public const SEND = 'email-login.send';

    public const VERIFY = 'email-login.verify';

    public function __construct(private EmailLoginResponseFactory $responses) {}

    /**
     * @return array<int, Limit>
     */
    public function send(Request $request): array
    {
        $email = $this->emailFor($request);
        $ip = $request->ip() ?? 'unknown';
        $pending = PendingEmailLogin::from($request);
        $generationBeforeRequest = $pending->generation();
        $response = fn (Request $request, array $headers) => $this->responses->sendRateLimited(
            $email,
            (int) ($headers['Retry-After'] ?? 60),
            $request->routeIs('login.resend'),
            $headers,
        );
        $after = function (Response $response) use ($pending, $generationBeforeRequest): bool {
            $generationAfterRequest = $pending->generation();

            return $generationAfterRequest !== null
                && $generationAfterRequest !== $generationBeforeRequest;
        };

        return [
            Limit::perMinute(1)
                ->by('cooldown:'.hash('sha256', $email))
                ->after($after)
                ->response($response),
            Limit::perMinutes(15, 3)
                ->by('email-ip:'.hash('sha256', $email.'|'.$ip))
                ->after($after)
                ->response($response),
            Limit::perHour(20)
                ->by('ip:'.hash('sha256', $ip))
                ->after($after)
                ->response($response),
        ];
    }

    public function verify(Request $request): Limit|Unlimited
    {
        $pending = PendingEmailLogin::from($request);
        $generation = $pending->generation();

        if (! $generation) {
            return Limit::none();
        }

        return Limit::perMinutes(10, 5)
            ->by('challenge:'.hash('sha256', $generation.'|'.($request->ip() ?? 'unknown')))
            ->response(function (Request $request, array $headers) use ($pending) {
                $pending->challenge()?->delete();
                $pending->clear();

                return $this->responses->challengeRequired(
                    'Too many verification attempts. Request a new code.',
                )->withHeaders($headers);
            });
    }

    private function emailFor(Request $request): string
    {
        $email = $request->routeIs('login.resend')
            ? PendingEmailLogin::from($request)->email()
            : $request->input('email');

        return mb_strtolower(trim(is_string($email) ? $email : 'unknown'));
    }
}
