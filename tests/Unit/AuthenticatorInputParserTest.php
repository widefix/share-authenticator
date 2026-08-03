<?php

namespace Tests\Unit;

use App\Enums\TotpAlgorithm;
use App\Enums\TotpDigits;
use App\Services\Authenticators\AuthenticatorInputParser;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AuthenticatorInputParserTest extends TestCase
{
    private AuthenticatorInputParser $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new AuthenticatorInputParser;
    }

    public function test_it_parses_an_interoperable_uri_and_defaults(): void
    {
        $data = $this->parser->parse([
            'uri' => 'otpauth://totp/Example%3Aalice%40example.com?secret=JBSWY3DPEHPK3PXP&issuer=Example',
        ]);

        $this->assertSame('Example', $data->issuer);
        $this->assertSame('alice@example.com', $data->account);
        $this->assertSame(TotpAlgorithm::Sha1, $data->algorithm);
        $this->assertSame(TotpDigits::Six, $data->digits);
        $this->assertSame(30, $data->period);
    }

    public function test_it_rejects_hotp_conflicting_issuers_and_invalid_base32(): void
    {
        foreach ([
            'otpauth://hotp/Example:a?secret=JBSWY3DPEHPK3PXP&issuer=Example&counter=1',
            'otpauth://totp/One:a?secret=JBSWY3DPEHPK3PXP&issuer=Two',
            'otpauth://totp/Example:a?secret=NOT-BASE32&issuer=Example',
        ] as $uri) {
            try {
                $this->parser->parse(['uri' => $uri]);
                $this->fail('Invalid URI was accepted.');
            } catch (ValidationException) {
                $this->addToAssertionCount(1);
            }
        }
    }
}
