<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\AuthenticatorResource;
use App\Models\Vault;
use App\Services\Authenticators\AuthenticatorCipher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

final readonly class AuthenticatorFeedController
{
    public function __construct(private AuthenticatorCipher $cipher) {}

    public function __invoke(Request $request, Vault $vault): JsonResponse
    {
        Gate::authorize('view', $vault);
        $query = $vault->authenticators()->orderBy('id');
        if (! $request->user()->isWorkspaceAdmin()) {
            $query->active();
        }
        $items = $query->get()->map(fn ($authenticator) => [
            'authenticator' => $authenticator,
            'data' => $this->cipher->decrypt($authenticator),
        ]);

        return AuthenticatorResource::collection($items)
            ->additional(['meta' => ['server_time' => now()->toIso8601String()]])
            ->response()
            ->header('Cache-Control', 'no-store, private')
            ->header('Pragma', 'no-cache');
    }
}
