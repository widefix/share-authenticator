<?php

namespace App\Services\Auth;

use App\Data\IssuedLoginCode;
use App\Models\EmailLoginCode;
use Illuminate\Contracts\Session\Session;
use Illuminate\Http\Request;

final readonly class PendingEmailLogin
{
    public const EMAIL_KEY = 'login.pending_email';

    public const GENERATION_KEY = 'login.pending_generation';

    public function __construct(private Session $session) {}

    public static function from(Request $request): self
    {
        return new self($request->session());
    }

    public function email(): ?string
    {
        $email = $this->session->get(self::EMAIL_KEY);

        return is_string($email) ? $email : null;
    }

    public function generation(): ?string
    {
        $generation = $this->session->get(self::GENERATION_KEY);

        return is_string($generation) ? $generation : null;
    }

    public function challenge(): ?EmailLoginCode
    {
        $email = $this->email();
        $generation = $this->generation();

        if (! $email || ! $generation) {
            return null;
        }

        return EmailLoginCode::query()
            ->where('email', $email)
            ->where('generation', $generation)
            ->first();
    }

    public function replace(IssuedLoginCode $challenge): void
    {
        $this->session->put([
            self::EMAIL_KEY => $challenge->email,
            self::GENERATION_KEY => $challenge->generation,
        ]);
    }

    public function clear(): void
    {
        $this->session->forget([self::EMAIL_KEY, self::GENERATION_KEY]);
    }
}
