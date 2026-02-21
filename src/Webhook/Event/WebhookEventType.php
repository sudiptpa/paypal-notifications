<?php

declare(strict_types=1);

namespace Sujip\PayPal\Notifications\Webhook\Event;

enum WebhookEventType: string
{
    case PaymentCaptureCompleted = 'PAYMENT.CAPTURE.COMPLETED';
    case PaymentCaptureDenied = 'PAYMENT.CAPTURE.DENIED';
    case PaymentCaptureRefunded = 'PAYMENT.CAPTURE.REFUNDED';
    case PaymentCapturePending = 'PAYMENT.CAPTURE.PENDING';
    case PaymentCaptureReversed = 'PAYMENT.CAPTURE.REVERSED';

    case CheckoutOrderApproved = 'CHECKOUT.ORDER.APPROVED';
    case CheckoutOrderCompleted = 'CHECKOUT.ORDER.COMPLETED';

    case CustomerDisputeCreated = 'CUSTOMER.DISPUTE.CREATED';
    case CustomerDisputeResolved = 'CUSTOMER.DISPUTE.RESOLVED';

    case BillingSubscriptionCreated = 'BILLING.SUBSCRIPTION.CREATED';
    case BillingSubscriptionCancelled = 'BILLING.SUBSCRIPTION.CANCELLED';
    case BillingSubscriptionActivated = 'BILLING.SUBSCRIPTION.ACTIVATED';
    case BillingSubscriptionSuspended = 'BILLING.SUBSCRIPTION.SUSPENDED';
    case BillingSubscriptionExpired = 'BILLING.SUBSCRIPTION.EXPIRED';
    case BillingSubscriptionPaymentFailed = 'BILLING.SUBSCRIPTION.PAYMENT.FAILED';

    case PaymentPayoutsBatchSuccess = 'PAYMENT.PAYOUTSBATCH.SUCCESS';
    case PaymentPayoutsItemSucceeded = 'PAYMENT.PAYOUTS-ITEM.SUCCEEDED';
    case PaymentPayoutsItemDenied = 'PAYMENT.PAYOUTS-ITEM.DENIED';
}
