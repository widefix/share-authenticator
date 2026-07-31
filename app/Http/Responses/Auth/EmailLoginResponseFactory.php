<?php

namespace App\Http\Responses\Auth;

use App\Enums\LoginCodeVerificationStatus;
use App\Models\EmailLoginCode;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

final class EmailLoginResponseFactory
{
    public function challengeRequired(string $message): RedirectResponse
    {
        return redirect()->route('login')->withErrors(['email' => $message]);
    }

    public function challengeIssued(bool $isResend): RedirectResponse
    {
        $status = $isResend
            ? 'A new code is on its way. Only the newest code will work.'
            : 'If the address can receive email, a sign-in code is on its way.';

        return redirect()->route('login.verify')->with('status', $status);
    }

    public function verificationForm(EmailLoginCode $challenge): View
    {
        return view('auth.verify', [
            'maskedEmail' => $this->maskEmail($challenge->email),
            'expiresAt' => $challenge->expires_at->getTimestampMs(),
            'resendAt' => $challenge->updated_at->copy()->addMinute()->getTimestampMs(),
            'deliveryFailed' => $challenge->delivery_failed_at !== null,
        ]);
    }

    public function invalidCode(): RedirectResponse
    {
        return back()->withErrors([
            'code' => 'That code is not correct. Check the email and try again.',
        ]);
    }

    public function unavailableCode(LoginCodeVerificationStatus $status): RedirectResponse
    {
        $message = $status === LoginCodeVerificationStatus::Locked
            ? 'Too many incorrect attempts. Request a new code.'
            : 'That code has expired. Request a new code.';

        return $this->challengeRequired($message);
    }

    public function authenticated(): RedirectResponse
    {
        return redirect()->intended(route('dashboard'));
    }

    public function sendRateLimited(
        string $email,
        int $retryAfter,
        bool $isResend,
        array $headers,
    ): RedirectResponse {
        $message = 'Please wait '.$retryAfter.' seconds before requesting another code.';

        if ($isResend) {
            return redirect()->route('login.verify')
                ->withErrors(['resend' => $message])
                ->withHeaders($headers);
        }

        return redirect()->route('login')
            ->withErrors(['email' => $message])
            ->withInput(['email' => $email])
            ->withHeaders($headers);
    }

    private function maskEmail(string $email): string
    {
        [$local, $domain] = explode('@', $email, 2);
        $visible = mb_substr($local, 0, 1);

        if (mb_strlen($local) > 2) {
            $visible .= str_repeat('•', min(5, mb_strlen($local) - 2));
            $visible .= mb_substr($local, -1);
        } else {
            $visible .= '•';
        }

        return $visible.'@'.$domain;
    }
}
