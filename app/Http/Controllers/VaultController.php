<?php

namespace App\Http\Controllers;

use App\Http\Requests\DeleteVaultRequest;
use App\Http\Requests\StoreVaultRequest;
use App\Http\Requests\UpdateVaultRequest;
use App\Http\Responses\VaultResponseFactory;
use App\Models\Vault;
use App\Services\Vaults\VaultService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

final class VaultController extends Controller
{
    public function __construct(
        private readonly VaultService $vaults,
        private readonly VaultResponseFactory $responses,
    ) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Vault::class);

        $workspace = $request->user()
            ->workspace()
            ->withCount('users')
            ->firstOrFail();
        $activeVaults = $workspace->vaults()->active()->orderBy('name')->get();
        $archivedVaults = $workspace->vaults()->archived()->orderBy('name')->get();

        return $this->responses->index($workspace, $activeVaults, $archivedVaults);
    }

    public function store(StoreVaultRequest $request): RedirectResponse
    {
        $this->vaults->create(
            $request->user(),
            $request->string('name')->toString(),
        );

        return $this->responses->created();
    }

    public function update(UpdateVaultRequest $request, Vault $vault): RedirectResponse
    {
        $this->vaults->rename(
            $request->user(),
            $vault,
            $request->string('name')->toString(),
        );

        return $this->responses->renamed();
    }

    public function destroy(DeleteVaultRequest $request, Vault $vault): RedirectResponse
    {
        $summary = $this->vaults->delete(
            $request->user(),
            $vault,
            $request->string('confirmation')->toString(),
        );

        return $this->responses->deleted($summary);
    }
}
