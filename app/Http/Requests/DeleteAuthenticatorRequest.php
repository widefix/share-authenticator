<?php

namespace App\Http\Requests;

use App\Models\Authenticator;
use Illuminate\Foundation\Http\FormRequest;

class DeleteAuthenticatorRequest extends FormRequest
{
    public function authorize(): bool
    {
        $authenticator = $this->route('authenticator');

        return $authenticator instanceof Authenticator
            && ($this->user()?->can('delete', $authenticator) ?? false);
    }

    public function rules(): array
    {
        return ['confirmation' => ['required', 'string', 'max:241']];
    }
}
