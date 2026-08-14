<?php

namespace App\Http\Controllers;

use App\Http\Requests\DeleteAuthenticatorRequest;
use App\Http\Requests\StoreAuthenticatorRequest;
use App\Http\Requests\UpdateAuthenticatorRequest;
use App\Http\Responses\AuthenticatorResponseFactory;
use App\Models\Authenticator;
use App\Models\Vault;
use App\Services\Authenticators\AuthenticatorCipher;
use App\Services\Authenticators\AuthenticatorInputParser;
use App\Services\Authenticators\AuthenticatorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

final readonly class AuthenticatorController
{
    public function __construct(
        private AuthenticatorService $authenticators,
        private AuthenticatorInputParser $parser,
        private AuthenticatorCipher $cipher,
        private AuthenticatorResponseFactory $responses,
    ) {}

    public function index(Request $request, Vault $vault): View
    {
        Gate::authorize('view', $vault);

        $archivedCount = $request->user()->isWorkspaceAdmin()
            ? fn ($query) => $query->archived()
            : fn ($query) => $query->whereRaw('1 = 0');

        return $this->responses->index($vault->loadCount([
            'authenticators as active_authenticators_count' => fn ($query) => $query->active(),
            'authenticators as archived_authenticators_count' => $archivedCount,
        ]));
    }

    public function create(Request $request, Vault $vault): View
    {
        Gate::authorize('create', Authenticator::class);
        Gate::authorize('view', $vault);

        return $this->responses->create($vault, $this->activeVaults($request));
    }

    public function store(StoreAuthenticatorRequest $request, Vault $vault): RedirectResponse|JsonResponse
    {
        $authenticator = $this->authenticators->create(
            $request->user(),
            $vault,
            $this->parser->parse($request->validated()),
            $request->boolean('confirm_duplicate'),
        );

        if ($request->expectsJson()) {
            return response()->json(['redirect' => route('vaults.authenticators.index', $authenticator->vault)]);
        }

        return $this->responses->created($authenticator);
    }

    public function edit(Request $request, Authenticator $authenticator): View
    {
        Gate::authorize('update', $authenticator);

        return $this->responses->edit(
            $authenticator->load('vault'),
            $this->cipher->decrypt($authenticator),
            $this->activeVaults($request),
        );
    }

    public function update(UpdateAuthenticatorRequest $request, Authenticator $authenticator): RedirectResponse|JsonResponse
    {
        $targetVault = Vault::query()->findOrFail($request->integer('vault_id'));
        $replacement = null;
        if ($request->filled('replacement_uri') || $request->filled('replacement_secret')) {
            $replacement = $this->parser->parse([
                'uri' => $request->input('replacement_uri'),
                'issuer' => $request->input('issuer'),
                'account' => $request->input('account'),
                'secret' => $request->input('replacement_secret'),
                'algorithm' => $request->input('replacement_algorithm'),
                'digits' => $request->input('replacement_digits'),
                'period' => $request->input('replacement_period'),
            ]);
        }

        $updated = $this->authenticators->update(
            $request->user(),
            $authenticator,
            $targetVault,
            trim($request->string('issuer')->toString()),
            trim($request->string('account')->toString()),
            $replacement,
            $request->boolean('confirm_sensitive_change'),
            $request->boolean('confirm_duplicate'),
        );

        if ($request->expectsJson()) {
            return response()->json(['redirect' => route('vaults.authenticators.index', $updated->vault)]);
        }

        return $this->responses->updated($updated);
    }

    public function destroy(DeleteAuthenticatorRequest $request, Authenticator $authenticator): RedirectResponse
    {
        $vault = $authenticator->vault;
        $this->authenticators->delete($request->user(), $authenticator, $request->string('confirmation')->toString());

        return $this->responses->deleted($vault);
    }

    private function activeVaults(Request $request)
    {
        return $request->user()->workspace->vaults()->active()->orderBy('name')->get();
    }
}
