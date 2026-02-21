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
        $eventType = (string) ($payload['event_type'] ?? '');

        return match ($eventType) {
            'PAYMENT.CAPTURE.COMPLETED' => new PaymentCaptureCompletedEvent($payload),
            'PAYMENT.CAPTURE.DENIED' => new PaymentCaptureDeniedEvent($payload),
            'PAYMENT.CAPTURE.REFUNDED' => new PaymentCaptureRefundedEvent($payload),
            'CHECKOUT.ORDER.APPROVED' => new CheckoutOrderApprovedEvent($payload),
            default => new UnknownWebhookEvent($payload),
        };
    }
}
