<?php

namespace App\Data;

use App\Enums\TotpAlgorithm;
use App\Enums\TotpDigits;

final readonly class AuthenticatorData
{
    public function __construct(
        public string $issuer,
        public string $account,
        #[\SensitiveParameter]
        public string $secret,
        public TotpAlgorithm $algorithm,
        public TotpDigits $digits,
        public int $period,
    ) {}

    /** @return array{issuer: string, account: string, secret: string, algorithm: string, digits: int, period: int} */
    public function toArray(): array
    {
        return [
            'issuer' => $this->issuer,
            'account' => $this->account,
            'secret' => $this->secret,
            'algorithm' => $this->algorithm->value,
            'digits' => $this->digits->value,
            'period' => $this->period,
        ];
    }

    /** @param array{issuer: string, account: string, secret: string, algorithm: string, digits: int, period: int} $data */
    public static function fromArray(#[\SensitiveParameter] array $data): self
    {
        return new self(
            issuer: $data['issuer'],
            account: $data['account'],
            secret: $data['secret'],
            algorithm: TotpAlgorithm::from($data['algorithm']),
            digits: TotpDigits::from($data['digits']),
            period: $data['period'],
        );
    }

    public function label(): string
    {
        return "{$this->issuer}: {$this->account}";
    }
}
