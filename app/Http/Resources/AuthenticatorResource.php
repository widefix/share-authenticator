<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuthenticatorResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $authenticator = $this->resource['authenticator'];
        $data = $this->resource['data'];

        return [
            'id' => $authenticator->uuid,
            'issuer' => $data->issuer,
            'account' => $data->account,
            'algorithm' => $data->algorithm->value,
            'digits' => $data->digits->value,
            'period' => $data->period,
            'archived' => $authenticator->isArchived(),
            'can_manage' => $request->user()->isWorkspaceAdmin(),
            'edit_url' => $request->user()->isWorkspaceAdmin() ? route('authenticators.edit', $authenticator) : null,
            'reveal_url' => route('api.authenticators.reveal', $authenticator),
            'copy_url' => route('api.authenticators.copy', $authenticator),
            'archive_url' => $request->user()->isWorkspaceAdmin() ? route('authenticators.archive', $authenticator) : null,
            'restore_url' => $request->user()->isWorkspaceAdmin() ? route('authenticators.restore', $authenticator) : null,
            'delete_url' => $request->user()->isWorkspaceAdmin() ? route('authenticators.destroy', $authenticator) : null,
        ];
    }
}
