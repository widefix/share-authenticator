<?php

namespace App\Http\Requests;

use App\Models\Vault;
use App\Services\Shared\NameNormalizer;
use Illuminate\Foundation\Http\FormRequest;

class StoreVaultRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Vault::class) ?? false;
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
