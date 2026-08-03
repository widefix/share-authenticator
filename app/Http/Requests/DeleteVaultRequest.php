<?php

namespace App\Http\Requests;

use App\Models\Vault;
use Illuminate\Foundation\Http\FormRequest;

class DeleteVaultRequest extends FormRequest
{
    public function authorize(): bool
    {
        $vault = $this->route('vault');

        return $vault instanceof Vault
            && ($this->user()?->can('delete', $vault) ?? false);
    }

    public function rules(): array
    {
        return [
            'confirmation' => ['required', 'string', 'max:80'],
        ];
    }
}
