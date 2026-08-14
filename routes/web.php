<?php

use App\Http\Controllers\Api\AuthenticatorCodeController;
use App\Http\Controllers\Api\AuthenticatorFeedController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\AuthenticatorArchiveController;
use App\Http\Controllers\AuthenticatorController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\MembershipController;
use App\Http\Controllers\VaultArchiveController;
use App\Http\Controllers\VaultController;
use App\Http\Controllers\WorkspaceController;
use App\Http\Controllers\WorkspaceInvitationController;
use App\Http\RateLimiters\AuthenticationRateLimits;
use App\Http\RateLimiters\AuthenticatorCodeRateLimits;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])
        ->middleware(ThrottleRequests::using(AuthenticationRateLimits::LOGIN))
        ->name('login.store');
    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store'])
        ->middleware(ThrottleRequests::using(AuthenticationRateLimits::REGISTER))
        ->name('register.store');
    Route::post('/invitations/{invitation}/{token}/register', [WorkspaceInvitationController::class, 'register'])
        ->middleware(ThrottleRequests::using(AuthenticationRateLimits::REGISTER))
        ->name('invitations.register');
});

Route::get('/invitations/{invitation}/{token}', [WorkspaceInvitationController::class, 'show'])
    ->name('invitations.show');

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
    Route::get('/members', MembershipController::class)->name('members.index');
    Route::post('/invitations', [WorkspaceInvitationController::class, 'store'])->name('invitations.store');
    Route::post('/invitations/{invitation}/revoke', [WorkspaceInvitationController::class, 'revoke'])->name('invitations.revoke');
    Route::post('/invitations/{invitation}/{token}', [WorkspaceInvitationController::class, 'accept'])->name('invitations.accept');
    Route::patch('/members/{member}', [MemberController::class, 'update'])->name('members.update');
    Route::delete('/members/{member}', [MemberController::class, 'destroy'])->name('members.destroy');
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
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
});
