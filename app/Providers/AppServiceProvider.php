<?php

namespace App\Providers;

use App\Http\RateLimiters\AuthenticatorCodeRateLimits;
use App\Http\RateLimiters\EmailLoginRateLimits;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(EmailLoginRateLimits $limits, AuthenticatorCodeRateLimits $authenticatorCodes): void
    {
        RateLimiter::for(EmailLoginRateLimits::SEND, $limits->send(...));
        RateLimiter::for(EmailLoginRateLimits::VERIFY, $limits->verify(...));
        RateLimiter::for(AuthenticatorCodeRateLimits::NAME, $authenticatorCodes(...));
    }
}
