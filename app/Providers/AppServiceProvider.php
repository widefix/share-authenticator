<?php

namespace App\Providers;

use App\Http\RateLimiters\AuthenticationRateLimits;
use App\Http\RateLimiters\AuthenticatorCodeRateLimits;
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
    public function boot(AuthenticationRateLimits $authentication, AuthenticatorCodeRateLimits $authenticatorCodes): void
    {
        RateLimiter::for(AuthenticationRateLimits::LOGIN, $authentication->login(...));
        RateLimiter::for(AuthenticationRateLimits::REGISTER, $authentication->register(...));
        RateLimiter::for(AuthenticatorCodeRateLimits::NAME, $authenticatorCodes(...));
    }
}
