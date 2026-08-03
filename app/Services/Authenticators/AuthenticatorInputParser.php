<?php

namespace App\Services\Authenticators;

use App\Data\AuthenticatorData;
use App\Enums\TotpAlgorithm;
use App\Enums\TotpDigits;
use Illuminate\Validation\ValidationException;
use ParagonIE\ConstantTime\Base32;
use Throwable;

final class AuthenticatorInputParser
{
    /** @param array<string, mixed> $input */
    public function parse(#[\SensitiveParameter] array $input): AuthenticatorData
    {
        if (filled($input['uri'] ?? null)) {
            return $this->parseUri((string) $input['uri']);
        }

        return $this->fromFields($input);
    }

    public function canonicalSecret(#[\SensitiveParameter] string $secret): string
    {
        $canonical = strtoupper((string) preg_replace('/\s+/', '', trim($secret)));
        $canonical = rtrim($canonical, '=');

        if ($canonical === '' || strlen($canonical) > 256 || ! preg_match('/^[A-Z2-7]+$/', $canonical)) {
            $this->invalid('secret', 'Enter a valid Base32 secret.');
        }

        try {
            $decoded = Base32::decodeUpper($canonical);
        } catch (Throwable) {
            $this->invalid('secret', 'Enter a valid Base32 secret.');
        }

        if ($decoded === '' || strlen($decoded) > 128) {
            $this->invalid('secret', 'The decoded secret must contain between 1 and 128 bytes.');
        }

        return $canonical;
    }

    private function parseUri(#[\SensitiveParameter] string $uri): AuthenticatorData
    {
        $uri = trim($uri);
        $parts = parse_url($uri);

        if (! is_array($parts) || strtolower((string) ($parts['scheme'] ?? '')) !== 'otpauth') {
            $this->invalid('uri', 'Enter a valid otpauth://totp URI.');
        }

        if (strtolower((string) ($parts['host'] ?? '')) !== 'totp') {
            $this->invalid('uri', 'Only time-based TOTP authenticators are supported.');
        }

        $label = rawurldecode(ltrim((string) ($parts['path'] ?? ''), '/'));
        parse_str((string) ($parts['query'] ?? ''), $parameters);

        $labelIssuer = null;
        $account = $label;
        if (str_contains($label, ':')) {
            [$labelIssuer, $account] = explode(':', $label, 2);
            $labelIssuer = trim($labelIssuer);
            $account = trim($account);
        }

        $queryIssuer = trim((string) ($parameters['issuer'] ?? ''));
        if ($labelIssuer !== null && $queryIssuer !== '' && ! hash_equals($labelIssuer, $queryIssuer)) {
            $this->invalid('uri', 'The issuer in the label and issuer parameter must match.');
        }

        return $this->fromFields([
            'issuer' => $queryIssuer !== '' ? $queryIssuer : $labelIssuer,
            'account' => $account,
            'secret' => $parameters['secret'] ?? null,
            'algorithm' => $parameters['algorithm'] ?? TotpAlgorithm::Sha1->value,
            'digits' => $parameters['digits'] ?? TotpDigits::Six->value,
            'period' => $parameters['period'] ?? 30,
        ]);
    }

    /** @param array<string, mixed> $input */
    private function fromFields(#[\SensitiveParameter] array $input): AuthenticatorData
    {
        $issuer = $this->label($input['issuer'] ?? null, 'issuer', 'Issuer');
        $account = $this->label($input['account'] ?? null, 'account', 'Account');
        $secret = $this->canonicalSecret((string) ($input['secret'] ?? ''));

        try {
            $algorithm = TotpAlgorithm::from(strtoupper((string) ($input['algorithm'] ?? 'SHA1')));
        } catch (Throwable) {
            $this->invalid('algorithm', 'Choose SHA1, SHA256, or SHA512.');
        }

        try {
            $digits = TotpDigits::from((int) ($input['digits'] ?? 6));
        } catch (Throwable) {
            $this->invalid('digits', 'Codes must contain 6 or 8 digits.');
        }

        $period = filter_var($input['period'] ?? 30, FILTER_VALIDATE_INT);
        if ($period === false || $period < 15 || $period > 300) {
            $this->invalid('period', 'The period must be between 15 and 300 seconds.');
        }

        return new AuthenticatorData($issuer, $account, $secret, $algorithm, $digits, $period);
    }

    private function label(mixed $value, string $field, string $label): string
    {
        $value = trim((string) preg_replace('/\s+/u', ' ', (string) $value));

        if ($value === '' || mb_strlen($value) > 120 || str_contains($value, ':')) {
            $this->invalid($field, "{$label} is required, may not contain a colon, and must be 120 characters or fewer.");
        }

        return $value;
    }

    private function invalid(string $field, string $message): never
    {
        throw ValidationException::withMessages([$field => $message]);
    }
}
