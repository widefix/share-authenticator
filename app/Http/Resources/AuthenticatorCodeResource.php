<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuthenticatorCodeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'code' => $this->resource->code,
            'expires_at' => $this->resource->expiresAt->toIso8601String(),
            'server_time' => $this->resource->serverTime->toIso8601String(),
            'digits' => $this->resource->digits,
            'period' => $this->resource->period,
        ];
    }
}
