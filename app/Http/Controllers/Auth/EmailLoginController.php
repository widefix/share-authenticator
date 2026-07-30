<?php

namespace App\Http\Controllers\Auth;

use App\Enums\LoginCodeVerificationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\SendLoginCodeRequest;
use App\Http\Requests\VerifyLoginCodeRequest;
use App\Models\EmailLoginCode;
use App\Services\EmailLoginCodeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;

class EmailLoginController extends Controller
{
    private const PENDING_EMAIL = 'login.pending_email';

    private const PENDING_GENERATION = 'login.pending_generation';

    public function create(Request $request): View
    {
        $request->session()->forget([self::PENDING_EMAIL, self::PENDING_GENERATION]);

        return view('auth.login');
    }

    public function store(
        SendLoginCodeRequest $request,
        EmailLoginCodeService $codes,
    ): RedirectResponse {
        return $this->issueCode(
            $request,
            $codes,
            $codes->normalizeEmail($request->string('email')->toString()),
        );
    }

    public function verify(Request $request): View|RedirectResponse
    {
        $challenge = $this->pendingChallenge($request);

        if (! $challenge) {
            return redirect()->route('login')
                ->withErrors(['email' => 'Request a new sign-in code to continue.']);
        }

        return view('auth.verify', [
            'maskedEmail' => $this->maskEmail($challenge->email),
            'expiresAt' => $challenge->expires_at->getTimestampMs(),
            'resendAt' => $challenge->updated_at->addSeconds(60)->getTimestampMs(),
            'deliveryFailed' => $challenge->delivery_failed_at !== null,
        ]);
    }

    public function authenticate(
        VerifyLoginCodeRequest $request,
        EmailLoginCodeService $codes,
    ): RedirectResponse {
        $email = $request->session()->get(self::PENDING_EMAIL);
        $generation = $request->session()->get(self::PENDING_GENERATION);

        if (! is_string($email) || ! is_string($generation)) {
            return redirect()->route('login')
                ->withErrors(['email' => 'Request a new sign-in code to continue.']);
        }

        $result = $codes->verify(
            $email,
            $generation,
            $request->string('code')->toString(),
        );

        if ($result->status === LoginCodeVerificationStatus::Valid && $result->user) {
            RateLimiter::clear($this->verificationKey($email, $request->ip()));
            Auth::login($result->user);
            $request->session()->regenerate();
            $request->session()->forget([self::PENDING_EMAIL, self::PENDING_GENERATION]);

            return redirect()->intended(route('dashboard'));
        }

        if ($result->status === LoginCodeVerificationStatus::Invalid) {
            RateLimiter::hit($this->verificationKey($email, $request->ip()), 600);

            return back()->withErrors([
                'code' => 'That code is not correct. Check the email and try again.',
            ]);
        }

        $request->session()->forget([self::PENDING_EMAIL, self::PENDING_GENERATION]);

        $message = $result->status === LoginCodeVerificationStatus::Locked
            ? 'Too many incorrect attempts. Request a new code.'
            : 'That code has expired. Request a new code.';

        return redirect()->route('login')->withErrors(['email' => $message]);
    }

    public function resend(Request $request, EmailLoginCodeService $codes): RedirectResponse
    {
        $email = $request->session()->get(self::PENDING_EMAIL);

        if (! is_string($email)) {
            return redirect()->route('login')
                ->withErrors(['email' => 'Enter your email to request a sign-in code.']);
        }

        return $this->issueCode($request, $codes, $email, isResend: true);
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function issueCode(
        Request $request,
        EmailLoginCodeService $codes,
        string $email,
        bool $isResend = false,
    ): RedirectResponse {
        $ip = $request->ip();
        $cooldownKey = $this->cooldownKey($email);
        $emailAndIpKey = $this->sendKey($email, $ip);
        $ipKey = $this->ipSendKey($ip);

        if (RateLimiter::tooManyAttempts($cooldownKey, 1)) {
            $seconds = RateLimiter::availableIn($cooldownKey);

            return $this->rateLimitedResponse($request, $email, $seconds, $isResend);
        }

        if (RateLimiter::tooManyAttempts($emailAndIpKey, 3)) {
            $seconds = RateLimiter::availableIn($emailAndIpKey);

            return $this->rateLimitedResponse($request, $email, $seconds, $isResend);
        }

        if (RateLimiter::tooManyAttempts($ipKey, 20)) {
            $seconds = RateLimiter::availableIn($ipKey);

            return $this->rateLimitedResponse($request, $email, $seconds, $isResend);
        }

        $challenge = $codes->issue($email);

        RateLimiter::hit($cooldownKey, 60);
        RateLimiter::hit($emailAndIpKey, 900);
        RateLimiter::hit($ipKey, 3600);

        $request->session()->put([
            self::PENDING_EMAIL => $challenge['email'],
            self::PENDING_GENERATION => $challenge['generation'],
        ]);

        $status = $isResend
            ? 'A new code is on its way. Only the newest code will work.'
            : 'If the address can receive email, a sign-in code is on its way.';

        return redirect()->route('login.verify')->with('status', $status);
    }

    private function rateLimitedResponse(
        Request $request,
        string $email,
        int $seconds,
        bool $isResend,
    ): RedirectResponse {
        $message = 'Please wait '.$seconds.' seconds before requesting another code.';

        if ($isResend) {
            return redirect()->route('login.verify')->withErrors(['resend' => $message]);
        }

        return redirect()->route('login')
            ->withErrors(['email' => $message])
            ->withInput(['email' => $email]);
    }

    private function pendingChallenge(Request $request): ?EmailLoginCode
    {
        $email = $request->session()->get(self::PENDING_EMAIL);
        $generation = $request->session()->get(self::PENDING_GENERATION);

        if (! is_string($email) || ! is_string($generation)) {
            return null;
        }

        return EmailLoginCode::query()
            ->where('email', $email)
            ->where('generation', $generation)
            ->first();
    }

    private function cooldownKey(string $email): string
    {
        return 'login-code:cooldown:'.hash('sha256', $email);
    }

    private function sendKey(string $email, ?string $ip): string
    {
        return 'login-code:send:'.hash('sha256', $email.'|'.($ip ?? 'unknown'));
    }

    private function ipSendKey(?string $ip): string
    {
        return 'login-code:ip:'.hash('sha256', $ip ?? 'unknown');
    }

    private function verificationKey(string $email, ?string $ip): string
    {
        return 'login-code:verify:'.hash('sha256', $email.'|'.($ip ?? 'unknown'));
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
