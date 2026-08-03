<?php

namespace App\Http\Controllers\Api;

use App\Enums\AuditAction;
use App\Http\Resources\AuthenticatorCodeResource;
use App\Models\Authenticator;
use App\Services\Authenticators\AuthenticatorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

final readonly class AuthenticatorCodeController
{
    public function __construct(private AuthenticatorService $authenticators) {}

    public function reveal(Request $request, Authenticator $authenticator): JsonResponse
    {
        return $this->respond($request, $authenticator, AuditAction::AuthenticatorRevealed);
    }

    public function copy(Request $request, Authenticator $authenticator): JsonResponse
    {
        return $this->respond($request, $authenticator, AuditAction::AuthenticatorCopied);
    }

    private function respond(Request $request, Authenticator $authenticator, AuditAction $action): JsonResponse
    {
        Gate::authorize('view', $authenticator);
        $resource = new AuthenticatorCodeResource(
            $this->authenticators->code($request->user(), $authenticator, $action),
        );

        return $resource->response()
            ->header('Cache-Control', 'no-store, private')
            ->header('Pragma', 'no-cache');
    }
}
