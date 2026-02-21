<?php

declare(strict_types=1);

namespace Sujip\PayPal\Notifications\InstantPaymentNotification;

final readonly class VerifyInstantPaymentNotificationResult
{
    public function __construct(
        public InstantPaymentNotificationStatus $status,
        public string $rawResponseBody,
        public int $httpStatusCode,
        public ?string $errorMessage = null,
    ) {
    }

    public function isVerified(): bool
    {
        return $this->status === InstantPaymentNotificationStatus::VERIFIED;
    }

    public function isInvalid(): bool
    {
        return $this->status === InstantPaymentNotificationStatus::INVALID;
    }

    public function isError(): bool
    {
        return $this->status === InstantPaymentNotificationStatus::ERROR;
    }
}
