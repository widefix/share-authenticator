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

    public function login(Request $request): Limit
    {
        $email = mb_strtolower(trim((string) $request->input('email')));
        $key = hash('sha256', $email.'|'.($request->ip() ?? 'unknown'));

        return Limit::perMinute(5)
            ->by($key)
            ->response(fn (Request $request, array $headers) => $this->responses->rateLimited(
                (int) ($headers['Retry-After'] ?? 60),
                $headers,
            ));
    }

    public function register(Request $request): Limit
    {
        return Limit::perHour(20)->by(hash('sha256', $request->ip() ?? 'unknown'));
    }
}
