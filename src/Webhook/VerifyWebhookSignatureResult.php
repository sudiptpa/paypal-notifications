<?php

declare(strict_types=1);

namespace Sujip\PayPal\Notifications\Webhook;

use Sujip\PayPal\Notifications\Enum\VerificationStatus;

final readonly class VerifyWebhookSignatureResult
{
    /**
     * @param array<string,mixed> $rawResponse
     */
    public function __construct(
        public VerificationStatus $status,
        public string $paypalStatus,
        public ?string $debugId,
        public int $httpStatusCode,
        public array $rawResponse,
    ) {
    }

    public function isSuccess(): bool
    {
        return $this->status === VerificationStatus::SUCCESS;
    }

    public function isFailure(): bool
    {
        return !$this->isSuccess();
    }
}
