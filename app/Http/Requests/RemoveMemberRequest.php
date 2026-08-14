<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RemoveMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isWorkspaceAdmin() === true;
    }

    public function rules(): array
    {
        return ['confirmation' => ['required', 'string', 'max:254']];
    }
}
