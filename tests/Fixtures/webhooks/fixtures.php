<?php

declare(strict_types=1);

return [
    [
        'file' => 'payment_capture_completed.json',
        'event_type' => 'PAYMENT.CAPTURE.COMPLETED',
        'class' => \Sujip\PayPal\Notifications\Webhook\Event\PaymentCaptureCompletedEvent::class,
    ],
    [
        'file' => 'customer_dispute_created.json',
        'event_type' => 'CUSTOMER.DISPUTE.CREATED',
        'class' => \Sujip\PayPal\Notifications\Webhook\Event\CustomerDisputeCreatedEvent::class,
    ],
    [
        'file' => 'billing_subscription_cancelled.json',
        'event_type' => 'BILLING.SUBSCRIPTION.CANCELLED',
        'class' => \Sujip\PayPal\Notifications\Webhook\Event\BillingSubscriptionCancelledEvent::class,
    ],
];

