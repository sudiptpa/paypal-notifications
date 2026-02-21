<?php

declare(strict_types=1);

namespace Sujip\PayPal\Notifications\Enum;

enum VerificationStatus: string
{
    case SUCCESS = 'SUCCESS';
    case FAILURE = 'FAILURE';

    public static function fromString(?string $status): self
    {
        return self::tryFrom((string) $status) ?? self::FAILURE;
    }
}
