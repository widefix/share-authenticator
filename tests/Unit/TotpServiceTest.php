<?php

namespace Tests\Unit;

use App\Data\AuthenticatorData;
use App\Enums\TotpAlgorithm;
use App\Enums\TotpDigits;
use App\Services\Authenticators\TotpService;
use ParagonIE\ConstantTime\Base32;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class TotpServiceTest extends TestCase
{
    /** @return array<string, array{int, string, string, string}> */
    public static function rfcVectors(): array
    {
        return [
            '59' => [59, '94287082', '46119246', '90693936'],
            '1111111109' => [1111111109, '07081804', '68084774', '25091201'],
            '1111111111' => [1111111111, '14050471', '67062674', '99943326'],
            '1234567890' => [1234567890, '89005924', '91819424', '93441116'],
            '2000000000' => [2000000000, '69279037', '90698825', '38618901'],
            '20000000000' => [20000000000, '65353130', '77737706', '47863826'],
        ];
    }

    #[DataProvider('rfcVectors')]
    public function test_it_matches_every_rfc_6238_vector(
        int $timestamp,
        string $sha1,
        string $sha256,
        string $sha512,
    ): void {
        $service = new TotpService;
        $secrets = [
            TotpAlgorithm::Sha1->value => Base32::encodeUpperUnpadded('12345678901234567890'),
            TotpAlgorithm::Sha256->value => Base32::encodeUpperUnpadded('12345678901234567890123456789012'),
            TotpAlgorithm::Sha512->value => Base32::encodeUpperUnpadded('1234567890123456789012345678901234567890123456789012345678901234'),
        ];

        $vectors = [
            [TotpAlgorithm::Sha1, $sha1],
            [TotpAlgorithm::Sha256, $sha256],
            [TotpAlgorithm::Sha512, $sha512],
        ];

        foreach ($vectors as [$algorithm, $expected]) {
            $data = new AuthenticatorData('RFC', 'vector', $secrets[$algorithm->value], $algorithm, TotpDigits::Eight, 30);
            $this->assertSame($expected, $service->generate($data, $timestamp)->code);
        }
    }

    public function test_it_respects_digits_period_and_expiry_boundary(): void
    {
        $data = new AuthenticatorData(
            'Example',
            'account',
            'JBSWY3DPEHPK3PXP',
            TotpAlgorithm::Sha1,
            TotpDigits::Six,
            45,
        );

        $code = (new TotpService)->generate($data, 89);

        $this->assertMatchesRegularExpression('/^\d{6}$/', $code->code);
        $this->assertSame(90, $code->expiresAt->getTimestamp());
    }
}
