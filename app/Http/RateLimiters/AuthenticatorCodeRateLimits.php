<?php

namespace App\Http\RateLimiters;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;

final class AuthenticatorCodeRateLimits
{
    public const NAME = 'authenticator-code';

    /** @return array<int, Limit> */
    public function __invoke(Request $request): array
    {
        $userId = $request->user()?->getAuthIdentifier() ?? 'guest';
        $authenticator = $request->route('authenticator');
        $authenticatorId = is_object($authenticator) ? $authenticator->getKey() : 'unknown';

        return [
            Limit::perMinute(120)->by("user:{$userId}"),
            Limit::perMinute(20)->by("authenticator:{$userId}:{$authenticatorId}"),
        ];
    }
}
