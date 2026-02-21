<?php

declare(strict_types=1);

namespace Sujip\PayPal\Notifications\Webhook;

use Sujip\PayPal\Notifications\Enum\VerificationStatus;

final readonly class WebhookProcessingResult
{
    public function __construct(
        public bool $accepted,
        public bool $dispatched,
        public bool $duplicate,
        public ?string $eventId,
        public ?string $eventType,
        public ?VerificationStatus $verificationStatus,
        public int $durationMs,
        public string $message,
        /** @var array<string,mixed> */
        public array $context = [],
    ) {
    }
}
