<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Support\Facades\DB;

final class RegistrationService
{
    public function register(string $email, #[\SensitiveParameter] string $password): User
    {
        return DB::transaction(fn () => User::query()->create([
            'email' => mb_strtolower(trim($email)),
            'password' => $password,
        ]));
    }
}
