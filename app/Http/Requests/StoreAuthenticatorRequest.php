<?php

namespace App\Http\Requests;

use App\Models\Authenticator;
use App\Models\Vault;
use Illuminate\Foundation\Http\FormRequest;

class StoreAuthenticatorRequest extends FormRequest
{
    public function authorize(): bool
    {
        $vault = $this->route('vault');

        return $vault instanceof Vault
            && ($this->user()?->can('create', Authenticator::class) ?? false)
            && $this->user()?->workspace_id === $vault->workspace_id;
    }

    public function rules(): array
    {
        return [
            'uri' => ['nullable', 'string', 'max:2048'],
            'issuer' => ['nullable', 'string', 'max:120'],
            'account' => ['nullable', 'string', 'max:120'],
            'secret' => ['nullable', 'string', 'max:512'],
            'algorithm' => ['nullable', 'string', 'max:10'],
            'digits' => ['nullable', 'integer'],
            'period' => ['nullable', 'integer'],
            'confirm_duplicate' => ['nullable', 'boolean'],
        ];
    }
}
