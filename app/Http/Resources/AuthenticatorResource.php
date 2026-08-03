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
            'edit_url' => route('authenticators.edit', $authenticator),
            'reveal_url' => route('api.authenticators.reveal', $authenticator),
            'copy_url' => route('api.authenticators.copy', $authenticator),
            'archive_url' => route('authenticators.archive', $authenticator),
            'restore_url' => route('authenticators.restore', $authenticator),
            'delete_url' => route('authenticators.destroy', $authenticator),
        ];
    }
}
