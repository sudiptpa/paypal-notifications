<?php

declare(strict_types=1);

namespace Sujip\PayPal\Notifications\Auth;

final readonly class OAuthToken
{
    public function __construct(
        public string $accessToken,
        public \DateTimeImmutable $expiresAt,
    ) {
    }

    public function isExpiredAt(\DateTimeImmutable $now): bool
    {
        return $now >= $this->expiresAt;
    }
}
