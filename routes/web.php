<?php

use App\Http\Controllers\Auth\EmailLoginController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [EmailLoginController::class, 'create'])->name('login');
    Route::post('/login', [EmailLoginController::class, 'store'])->name('login.store');
    Route::get('/login/verify', [EmailLoginController::class, 'verify'])->name('login.verify');
    Route::post('/login/verify', [EmailLoginController::class, 'authenticate'])->name('login.authenticate');
    Route::post('/login/resend', [EmailLoginController::class, 'resend'])->name('login.resend');
});

Route::middleware('auth')->group(function () {
    Route::get('/', fn () => redirect()->route('dashboard'));
    Route::view('/dashboard', 'dashboard')->name('dashboard');
    Route::post('/logout', [EmailLoginController::class, 'destroy'])->name('logout');
});
