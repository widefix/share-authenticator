<?php

namespace App\Http\Controllers\Auth;

use App\Enums\LoginCodeVerificationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\SendLoginCodeRequest;
use App\Http\Requests\VerifyLoginCodeRequest;
use App\Http\Responses\Auth\EmailLoginResponseFactory;
use App\Services\Auth\EmailLoginCodeService;
use App\Services\Auth\PendingEmailLogin;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

final class EmailLoginController extends Controller
{
    public function __construct(
        private readonly EmailLoginCodeService $codes,
        private readonly EmailLoginResponseFactory $responses,
    ) {}

    public function create(Request $request): View
    {
        PendingEmailLogin::from($request)->clear();

        return view('auth.login');
    }

    public function store(SendLoginCodeRequest $request): RedirectResponse
    {
        return $this->issueCode(
            $request,
            $this->codes->normalizeEmail($request->string('email')->toString()),
            false,
        );
    }

    public function verify(Request $request): View|RedirectResponse
    {
        $challenge = PendingEmailLogin::from($request)->challenge();

        if (! $challenge) {
            return $this->responses->challengeRequired(
                'Request a new sign-in code to continue.',
            );
        }

        return $this->responses->verificationForm($challenge);
    }

    public function authenticate(VerifyLoginCodeRequest $request): RedirectResponse
    {
        $pending = PendingEmailLogin::from($request);
        $email = $pending->email();
        $generation = $pending->generation();

        if (! $email || ! $generation) {
            return $this->responses->challengeRequired(
                'Request a new sign-in code to continue.',
            );
        }

        $result = $this->codes->verify(
            $email,
            $generation,
            $request->string('code')->toString(),
        );

        if ($result->status === LoginCodeVerificationStatus::Valid && $result->user) {
            Auth::login($result->user);
            $request->session()->regenerate();
            $pending->clear();

            return $this->responses->authenticated();
        }

        if ($result->status === LoginCodeVerificationStatus::Invalid) {
            return $this->responses->invalidCode();
        }

        $pending->clear();

        return $this->responses->unavailableCode($result->status);
    }

    public function resend(Request $request): RedirectResponse
    {
        $email = PendingEmailLogin::from($request)->email();

        if (! $email) {
            return $this->responses->challengeRequired(
                'Enter your email to request a sign-in code.',
            );
        }

        return $this->issueCode($request, $email, true);
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
        string $email,
        bool $isResend,
    ): RedirectResponse {
        $challenge = $this->codes->issue($email);

        PendingEmailLogin::from($request)->replace($challenge);

        return $this->responses->challengeIssued($isResend);
    }
}
