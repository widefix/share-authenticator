<?php

namespace App\Services\Authenticators;

use App\Data\AuthenticatorCode;
use App\Data\AuthenticatorData;
use Carbon\CarbonImmutable;
use OTPHP\InternalClock;
use OTPHP\TOTP;

final class TotpService
{
    public function generate(#[\SensitiveParameter] AuthenticatorData $data, ?int $timestamp = null): AuthenticatorCode
    {
        $timestamp ??= now()->getTimestamp();
        $totp = TOTP::create(
            secret: $data->secret,
            period: $data->period,
            digest: $data->algorithm->digest(),
            digits: $data->digits->value,
            clock: new InternalClock,
        );
        $expiresAt = (intdiv($timestamp, $data->period) + 1) * $data->period;

        return new AuthenticatorCode(
            code: $totp->at($timestamp),
            expiresAt: CarbonImmutable::createFromTimestampUTC($expiresAt),
            serverTime: CarbonImmutable::createFromTimestampUTC($timestamp),
            digits: $data->digits->value,
            period: $data->period,
        );
    }
}
