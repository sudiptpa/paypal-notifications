<?php

declare(strict_types=1);

namespace Sujip\PayPal\Notifications\Webhook\Event;

final class EventFactory
{
    /**
     * @param array<string,mixed> $payload
     */
    public static function fromPayload(array $payload): WebhookEventInterface
    {
        // Phase 1: Always return a safe envelope. Specific event mappings
        // are added incrementally in later phases.
        return new UnknownWebhookEvent($payload);
    }
}
