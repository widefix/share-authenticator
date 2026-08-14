<?php

namespace App\Http\Responses\Auth;

use Illuminate\Http\RedirectResponse;

final class AuthenticationResponseFactory
{
    public function authenticated(): RedirectResponse
    {
        return redirect()->intended(route('dashboard'));
    }

    public function invalidCredentials(): RedirectResponse
    {
        return back()
            ->withErrors(['email' => 'The provided credentials are incorrect.'])
            ->onlyInput('email');
    }

    public function rateLimited(int $retryAfter, array $headers = []): RedirectResponse
    {
        return back()
            ->withErrors(['email' => "Too many sign-in attempts. Try again in {$retryAfter} seconds."])
            ->onlyInput('email')
            ->withHeaders($headers);
    }
}
