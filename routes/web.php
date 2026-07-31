<?php

use App\Http\Controllers\Auth\EmailLoginController;
use App\Http\RateLimiters\EmailLoginRateLimits;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [EmailLoginController::class, 'create'])->name('login');
    Route::post('/login', [EmailLoginController::class, 'store'])
        ->middleware(ThrottleRequests::using(EmailLoginRateLimits::SEND))
        ->name('login.store');
    Route::get('/login/verify', [EmailLoginController::class, 'verify'])->name('login.verify');
    Route::post('/login/verify', [EmailLoginController::class, 'authenticate'])
        ->middleware(ThrottleRequests::using(EmailLoginRateLimits::VERIFY))
        ->name('login.authenticate');
    Route::post('/login/resend', [EmailLoginController::class, 'resend'])
        ->middleware(ThrottleRequests::using(EmailLoginRateLimits::SEND))
        ->name('login.resend');
});

Route::middleware('auth')->group(function () {
    Route::get('/', fn () => redirect()->route('dashboard'));
    Route::view('/dashboard', 'dashboard')->name('dashboard');
    Route::post('/logout', [EmailLoginController::class, 'destroy'])->name('logout');
});
