<?php

use App\Http\Controllers\Api\AuthenticatorCodeController;
use App\Http\Controllers\Api\AuthenticatorFeedController;
use App\Http\Controllers\Auth\EmailLoginController;
use App\Http\Controllers\AuthenticatorArchiveController;
use App\Http\Controllers\AuthenticatorController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\VaultArchiveController;
use App\Http\Controllers\VaultController;
use App\Http\Controllers\WorkspaceController;
use App\Http\RateLimiters\AuthenticatorCodeRateLimits;
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
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::post('/workspaces', [WorkspaceController::class, 'store'])->name('workspaces.store');
    Route::get('/vaults', [VaultController::class, 'index'])->name('vaults.index');
    Route::post('/vaults', [VaultController::class, 'store'])->name('vaults.store');
    Route::patch('/vaults/{vault}', [VaultController::class, 'update'])->name('vaults.update');
    Route::post('/vaults/{vault}/archive', [VaultArchiveController::class, 'store'])->name('vaults.archive');
    Route::delete('/vaults/{vault}/archive', [VaultArchiveController::class, 'destroy'])->name('vaults.restore');
    Route::delete('/vaults/{vault}', [VaultController::class, 'destroy'])->name('vaults.destroy');
    Route::get('/vaults/{vault}/authenticators', [AuthenticatorController::class, 'index'])->name('vaults.authenticators.index');
    Route::get('/vaults/{vault}/authenticators/create', [AuthenticatorController::class, 'create'])->name('vaults.authenticators.create');
    Route::post('/vaults/{vault}/authenticators', [AuthenticatorController::class, 'store'])->name('vaults.authenticators.store');
    Route::get('/authenticators/{authenticator}/edit', [AuthenticatorController::class, 'edit'])->name('authenticators.edit');
    Route::patch('/authenticators/{authenticator}', [AuthenticatorController::class, 'update'])->name('authenticators.update');
    Route::post('/authenticators/{authenticator}/archive', [AuthenticatorArchiveController::class, 'store'])->name('authenticators.archive');
    Route::delete('/authenticators/{authenticator}/archive', [AuthenticatorArchiveController::class, 'destroy'])->name('authenticators.restore');
    Route::delete('/authenticators/{authenticator}', [AuthenticatorController::class, 'destroy'])->name('authenticators.destroy');

    Route::prefix('api')->group(function () {
        Route::get('/vaults/{vault}/authenticators', AuthenticatorFeedController::class)->name('api.vaults.authenticators.index');
        Route::post('/authenticators/{authenticator}/reveal', [AuthenticatorCodeController::class, 'reveal'])
            ->middleware(ThrottleRequests::using(AuthenticatorCodeRateLimits::NAME))
            ->name('api.authenticators.reveal');
        Route::post('/authenticators/{authenticator}/copy', [AuthenticatorCodeController::class, 'copy'])
            ->middleware(ThrottleRequests::using(AuthenticatorCodeRateLimits::NAME))
            ->name('api.authenticators.copy');
    });
    Route::post('/logout', [EmailLoginController::class, 'destroy'])->name('logout');
});
