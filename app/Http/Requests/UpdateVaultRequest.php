<?php

namespace App\Http\Requests;

use App\Models\Vault;
use App\Services\Shared\NameNormalizer;
use Illuminate\Foundation\Http\FormRequest;

class UpdateVaultRequest extends FormRequest
{
    public function authorize(): bool
    {
        $vault = $this->route('vault');

        return $vault instanceof Vault
            && ($this->user()?->can('update', $vault) ?? false);
    }

    public function rules(): array
    {
        return [
            'name' => ['bail', 'required', 'string', 'min:1', 'max:80'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => app(NameNormalizer::class)->display((string) $this->input('name')),
        ]);
    }
}
