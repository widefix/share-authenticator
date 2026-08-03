<?php

use App\Http\Controllers\Auth\EmailLoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\VaultArchiveController;
use App\Http\Controllers\VaultController;
use App\Http\Controllers\WorkspaceController;
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
    Route::post('/logout', [EmailLoginController::class, 'destroy'])->name('logout');
});
