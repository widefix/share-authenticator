<?php

namespace App\Http\Requests;

use App\Services\Shared\NameNormalizer;
use Illuminate\Foundation\Http\FormRequest;

class StoreWorkspaceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->workspace_id === null;
    }

    public function rules(): array
    {
        return [
            'name' => ['bail', 'required', 'string', 'min:2', 'max:80'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => app(NameNormalizer::class)->display((string) $this->input('name')),
        ]);
    }
}
