<?php

namespace App\Http\Requests;

use App\Enums\WorkspaceRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWorkspaceInvitationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isWorkspaceAdmin() === true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email:rfc', 'max:254'],
            'role' => ['required', Rule::enum(WorkspaceRole::class)],
            'vault_ids' => ['nullable', 'array'],
            'vault_ids.*' => ['integer', 'distinct'],
        ];
    }
}
