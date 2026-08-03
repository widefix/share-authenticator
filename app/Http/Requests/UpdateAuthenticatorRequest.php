<?php

namespace App\Http\Requests;

use App\Models\Authenticator;
use Illuminate\Foundation\Http\FormRequest;

class UpdateAuthenticatorRequest extends FormRequest
{
    public function authorize(): bool
    {
        $authenticator = $this->route('authenticator');

        return $authenticator instanceof Authenticator
            && ($this->user()?->can('update', $authenticator) ?? false);
    }

    public function rules(): array
    {
        return [
            'vault_id' => ['required', 'integer', 'exists:vaults,id'],
            'issuer' => ['required', 'string', 'max:120'],
            'account' => ['required', 'string', 'max:120'],
            'replacement_uri' => ['nullable', 'string', 'max:2048'],
            'replacement_secret' => ['nullable', 'string', 'max:512'],
            'replacement_algorithm' => ['nullable', 'string', 'max:10'],
            'replacement_digits' => ['nullable', 'integer'],
            'replacement_period' => ['nullable', 'integer'],
            'confirm_sensitive_change' => ['nullable', 'boolean'],
            'confirm_duplicate' => ['nullable', 'boolean'],
        ];
    }
}
