<?php

namespace App\Http\Controllers;

use App\Http\Responses\AuthenticatorResponseFactory;
use App\Models\Authenticator;
use App\Services\Authenticators\AuthenticatorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

final readonly class AuthenticatorArchiveController
{
    public function __construct(
        private AuthenticatorService $authenticators,
        private AuthenticatorResponseFactory $responses,
    ) {}

    public function store(Request $request, Authenticator $authenticator): RedirectResponse
    {
        Gate::authorize('update', $authenticator);

        return $this->responses->archived($this->authenticators->archive($request->user(), $authenticator));
    }

    public function destroy(Request $request, Authenticator $authenticator): RedirectResponse
    {
        Gate::authorize('update', $authenticator);

        return $this->responses->restored($this->authenticators->restore($request->user(), $authenticator));
    }
}
