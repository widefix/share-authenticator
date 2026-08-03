<?php

namespace App\Http\Responses;

use App\Data\DeletedVault;
use App\Models\Vault;
use App\Models\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\View\View;

final class VaultResponseFactory
{
    /**
     * @param  Collection<int, Vault>  $activeVaults
     * @param  Collection<int, Vault>  $archivedVaults
     */
    public function index(
        Workspace $workspace,
        Collection $activeVaults,
        Collection $archivedVaults,
    ): View {
        return view('vaults.index', compact('workspace', 'activeVaults', 'archivedVaults'));
    }

    public function created(): RedirectResponse
    {
        return $this->settings('Vault created.');
    }

    public function renamed(): RedirectResponse
    {
        return $this->settings('Vault renamed.');
    }

    public function archived(): RedirectResponse
    {
        return $this->settings('Vault archived.');
    }

    public function restored(): RedirectResponse
    {
        return $this->settings('Vault restored.');
    }

    public function deleted(DeletedVault $summary): RedirectResponse
    {
        return $this->settings(
            "Vault deleted. {$summary->authenticatorCount} authenticators and "
            ."{$summary->memberCount} workspace members were affected.",
        );
    }

    private function settings(string $status): RedirectResponse
    {
        return redirect()->route('vaults.index')->with('status', $status);
    }
}
