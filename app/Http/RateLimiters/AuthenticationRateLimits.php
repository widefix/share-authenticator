<?php

namespace App\Http\RateLimiters;

use App\Http\Responses\Auth\AuthenticationResponseFactory;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;

final readonly class AuthenticationRateLimits
{
    public const LOGIN = 'authentication.login';

    public const REGISTER = 'authentication.register';

    public function __construct(private AuthenticationResponseFactory $responses) {}

    /** @return array<int, Limit> */
    public function login(Request $request): array
    {
        $email = mb_strtolower(trim((string) $request->input('email')));
        $ip = $request->ip() ?? 'unknown';
        $response = fn (Request $request, array $headers) => $this->responses->rateLimited(
            (int) ($headers['Retry-After'] ?? 60), $headers,
        );

        return [
            Limit::perMinute(5)->by('pair:'.hash('sha256', $email.'|'.$ip))->response($response),
            Limit::perMinute(20)->by('ip:'.hash('sha256', $ip))->response($response),
            Limit::perHour(30)->by('account:'.hash('sha256', $email))->response($response),
        ];
    }

    public function register(Request $request): Limit
    {
        return Limit::perHour(20)->by(hash('sha256', $request->ip() ?? 'unknown'));
    }
}
