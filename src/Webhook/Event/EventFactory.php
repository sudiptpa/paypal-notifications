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
            WebhookEventType::PaymentCaptureCompleted->value => new PaymentCaptureCompletedEvent($payload),
            WebhookEventType::PaymentCaptureDenied->value => new PaymentCaptureDeniedEvent($payload),
            WebhookEventType::PaymentCaptureRefunded->value => new PaymentCaptureRefundedEvent($payload),
            WebhookEventType::PaymentCapturePending->value => new PaymentCapturePendingEvent($payload),
            WebhookEventType::PaymentCaptureReversed->value => new PaymentCaptureReversedEvent($payload),
            WebhookEventType::CheckoutOrderApproved->value => new CheckoutOrderApprovedEvent($payload),
            WebhookEventType::CheckoutOrderCompleted->value => new CheckoutOrderCompletedEvent($payload),
            WebhookEventType::CustomerDisputeCreated->value => new CustomerDisputeCreatedEvent($payload),
            WebhookEventType::CustomerDisputeResolved->value => new CustomerDisputeResolvedEvent($payload),
            WebhookEventType::BillingSubscriptionCreated->value => new BillingSubscriptionCreatedEvent($payload),
            WebhookEventType::BillingSubscriptionCancelled->value => new BillingSubscriptionCancelledEvent($payload),
            WebhookEventType::BillingSubscriptionActivated->value => new BillingSubscriptionActivatedEvent($payload),
            WebhookEventType::BillingSubscriptionSuspended->value => new BillingSubscriptionSuspendedEvent($payload),
            WebhookEventType::BillingSubscriptionExpired->value => new BillingSubscriptionExpiredEvent($payload),
            WebhookEventType::BillingSubscriptionPaymentFailed->value => new BillingSubscriptionPaymentFailedEvent($payload),
            WebhookEventType::PaymentPayoutsBatchSuccess->value => new PaymentPayoutsBatchSuccessEvent($payload),
            WebhookEventType::PaymentPayoutsItemSucceeded->value => new PaymentPayoutsItemSucceededEvent($payload),
            WebhookEventType::PaymentPayoutsItemDenied->value => new PaymentPayoutsItemDeniedEvent($payload),
            default => new UnknownWebhookEvent($payload),
        };
    }
}
