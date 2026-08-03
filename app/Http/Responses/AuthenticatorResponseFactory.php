<?php

namespace App\Http\Responses;

use App\Data\AuthenticatorData;
use App\Models\Authenticator;
use App\Models\Vault;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

final class AuthenticatorResponseFactory
{
    public function index(Vault $vault): View
    {
        return view('authenticators.index', compact('vault'));
    }

    /** @param Collection<int, Vault> $vaults */
    public function create(Vault $vault, Collection $vaults): View
    {
        return view('authenticators.create', compact('vault', 'vaults'));
    }

    /** @param Collection<int, Vault> $vaults */
    public function edit(Authenticator $authenticator, AuthenticatorData $data, Collection $vaults): View
    {
        return view('authenticators.edit', compact('authenticator', 'data', 'vaults'));
    }

    public function created(Authenticator $authenticator): RedirectResponse
    {
        return redirect()->route('vaults.authenticators.index', $authenticator->vault)
            ->with('status', 'Authenticator saved.');
    }

    public function updated(Authenticator $authenticator): RedirectResponse
    {
        return redirect()->route('vaults.authenticators.index', $authenticator->vault)
            ->with('status', 'Authenticator updated.');
    }

    public function archived(Authenticator $authenticator): RedirectResponse
    {
        return redirect()->route('vaults.authenticators.index', $authenticator->vault)
            ->with('status', 'Authenticator archived.');
    }

    public function restored(Authenticator $authenticator): RedirectResponse
    {
        return redirect()->route('vaults.authenticators.index', $authenticator->vault)
            ->with('status', 'Authenticator restored.');
    }

    public function deleted(Vault $vault): RedirectResponse
    {
        return redirect()->route('vaults.authenticators.index', $vault)
            ->with('status', 'Authenticator permanently deleted.');
    }
}
