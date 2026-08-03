<?php

namespace App\Http\Controllers;

use App\Http\Responses\VaultResponseFactory;
use App\Models\Vault;
use App\Services\Vaults\VaultService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

final class VaultArchiveController extends Controller
{
    public function __construct(
        private readonly VaultService $vaults,
        private readonly VaultResponseFactory $responses,
    ) {}

    public function store(Request $request, Vault $vault): RedirectResponse
    {
        Gate::authorize('update', $vault);
        $this->vaults->archive($request->user(), $vault);

        return $this->responses->archived();
    }

    public function destroy(Request $request, Vault $vault): RedirectResponse
    {
        Gate::authorize('update', $vault);
        $this->vaults->restore($request->user(), $vault);

        return $this->responses->restored();
    }
}
