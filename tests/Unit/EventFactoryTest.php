<?php

declare(strict_types=1);

namespace Sujip\PayPal\Notifications\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Sujip\PayPal\Notifications\Webhook\Event\BillingSubscriptionActivatedEvent;
use Sujip\PayPal\Notifications\Webhook\Event\BillingSubscriptionCancelledEvent;
use Sujip\PayPal\Notifications\Webhook\Event\BillingSubscriptionCreatedEvent;
use Sujip\PayPal\Notifications\Webhook\Event\BillingSubscriptionExpiredEvent;
use Sujip\PayPal\Notifications\Webhook\Event\BillingSubscriptionPaymentFailedEvent;
use Sujip\PayPal\Notifications\Webhook\Event\BillingSubscriptionSuspendedEvent;
use Sujip\PayPal\Notifications\Webhook\Event\CheckoutOrderApprovedEvent;
use Sujip\PayPal\Notifications\Webhook\Event\CheckoutOrderCompletedEvent;
use Sujip\PayPal\Notifications\Webhook\Event\CustomerDisputeCreatedEvent;
use Sujip\PayPal\Notifications\Webhook\Event\CustomerDisputeResolvedEvent;
use Sujip\PayPal\Notifications\Webhook\Event\EventFactory;
use Sujip\PayPal\Notifications\Webhook\Event\PaymentCaptureCompletedEvent;
use Sujip\PayPal\Notifications\Webhook\Event\PaymentCaptureDeniedEvent;
use Sujip\PayPal\Notifications\Webhook\Event\PaymentCapturePendingEvent;
use Sujip\PayPal\Notifications\Webhook\Event\PaymentCaptureReversedEvent;
use Sujip\PayPal\Notifications\Webhook\Event\PaymentCaptureRefundedEvent;
use Sujip\PayPal\Notifications\Webhook\Event\PaymentPayoutsBatchSuccessEvent;
use Sujip\PayPal\Notifications\Webhook\Event\PaymentPayoutsItemDeniedEvent;
use Sujip\PayPal\Notifications\Webhook\Event\PaymentPayoutsItemSucceededEvent;
use Sujip\PayPal\Notifications\Webhook\Event\UnknownWebhookEvent;

final class EventFactoryTest extends TestCase
{
    public function testMapsPaymentCaptureCompletedEvent(): void
    {
        $payload = [
            'id' => 'WH-1',
            'event_type' => 'PAYMENT.CAPTURE.COMPLETED',
            'create_time' => '2026-01-01T00:00:00Z',
            'resource_type' => 'capture',
            'summary' => 'Payment completed',
            'resource' => [
                'id' => 'CAPTURE-123',
                'status' => 'COMPLETED',
            ],
        ];

        $event = EventFactory::fromPayload($payload);

        $this->assertInstanceOf(PaymentCaptureCompletedEvent::class, $event);
        $this->assertSame('WH-1', $event->id());
        $this->assertSame('PAYMENT.CAPTURE.COMPLETED', $event->eventType());
        $this->assertSame('2026-01-01T00:00:00Z', $event->createTime());
        $this->assertSame('capture', $event->resourceType());
        $this->assertSame('Payment completed', $event->summary());
        $this->assertSame('CAPTURE-123', $event->resource()['id']);
        $this->assertSame('COMPLETED', $event->captureStatus());
    }

    public function testMapsPaymentCaptureDeniedEvent(): void
    {
        $event = EventFactory::fromPayload([
            'id' => 'WH-2',
            'event_type' => 'PAYMENT.CAPTURE.DENIED',
            'resource' => [
                'id' => 'CAPTURE-456',
                'status' => 'DENIED',
                'amount' => ['value' => '10.00', 'currency_code' => 'USD'],
            ],
        ]);

        $this->assertInstanceOf(PaymentCaptureDeniedEvent::class, $event);
        $this->assertSame('CAPTURE-456', $event->captureId());
        $this->assertSame('DENIED', $event->captureStatus());
        $this->assertSame('10.00', $event->amountValue());
        $this->assertSame('USD', $event->amountCurrencyCode());
    }

    public function testMapsCheckoutOrderApprovedEvent(): void
    {
        $event = EventFactory::fromPayload([
            'id' => 'WH-3',
            'event_type' => 'CHECKOUT.ORDER.APPROVED',
            'resource' => [
                'id' => 'ORDER-123',
                'status' => 'APPROVED',
                'payer' => [
                    'payer_id' => 'PAYER123',
                    'email_address' => 'buyer@example.com',
                ],
                'purchase_units' => [
                    ['reference_id' => 'PUHF'],
                ],
            ],
        ]);

        $this->assertInstanceOf(CheckoutOrderApprovedEvent::class, $event);
        $this->assertSame('ORDER-123', $event->orderId());
        $this->assertSame('APPROVED', $event->orderStatus());
        $this->assertSame('PAYER123', $event->payerId());
        $this->assertSame('buyer@example.com', $event->payerEmail());
        $this->assertSame('PUHF', $event->purchaseUnitReferenceId());
    }

    public function testMapsPaymentCaptureRefundedEvent(): void
    {
        $event = EventFactory::fromPayload([
            'id' => 'WH-5',
            'event_type' => 'PAYMENT.CAPTURE.REFUNDED',
            'resource' => [
                'id' => 'REFUND-123',
                'status' => 'COMPLETED',
                'amount' => ['value' => '15.00', 'currency_code' => 'USD'],
                'invoice_id' => 'INV-1001',
                'note_to_payer' => 'Refund processed',
            ],
        ]);

        $this->assertInstanceOf(PaymentCaptureRefundedEvent::class, $event);
        $this->assertSame('REFUND-123', $event->refundId());
        $this->assertSame('COMPLETED', $event->refundStatus());
        $this->assertSame('15.00', $event->amountValue());
        $this->assertSame('USD', $event->amountCurrencyCode());
        $this->assertSame('INV-1001', $event->invoiceId());
        $this->assertSame('Refund processed', $event->noteToPayer());
    }

    public function testFallsBackToUnknownForUnmappedEventType(): void
    {
        $event = EventFactory::fromPayload([
            'id' => 'WH-4',
            'event_type' => 'CUSTOM.EVENT.TYPE',
            'resource' => ['foo' => 'bar'],
        ]);

        $this->assertInstanceOf(UnknownWebhookEvent::class, $event);
        $this->assertSame('CUSTOM.EVENT.TYPE', $event->eventType());
    }

    public function testMapsCustomerDisputeCreatedEvent(): void
    {
        $event = EventFactory::fromPayload([
            'id' => 'WH-6',
            'event_type' => 'CUSTOMER.DISPUTE.CREATED',
            'resource' => [
                'dispute_id' => 'PP-D-1',
                'status' => 'OPEN',
                'reason' => 'MERCHANDISE_OR_SERVICE_NOT_RECEIVED',
                'dispute_amount' => ['value' => '30.00', 'currency_code' => 'USD'],
            ],
        ]);

        $this->assertInstanceOf(CustomerDisputeCreatedEvent::class, $event);
        $this->assertSame('PP-D-1', $event->disputeId());
        $this->assertSame('OPEN', $event->disputeStatus());
        $this->assertSame('MERCHANDISE_OR_SERVICE_NOT_RECEIVED', $event->disputeReason());
        $this->assertSame('30.00', $event->amountValue());
        $this->assertSame('USD', $event->amountCurrencyCode());
    }

    public function testMapsCustomerDisputeResolvedEvent(): void
    {
        $event = EventFactory::fromPayload([
            'id' => 'WH-7',
            'event_type' => 'CUSTOMER.DISPUTE.RESOLVED',
            'resource' => [
                'dispute_id' => 'PP-D-2',
                'status' => 'RESOLVED',
                'dispute_outcome' => 'RESOLVED_BUYER_FAVOUR',
                'dispute_amount' => ['value' => '45.00', 'currency_code' => 'USD'],
            ],
        ]);

        $this->assertInstanceOf(CustomerDisputeResolvedEvent::class, $event);
        $this->assertSame('PP-D-2', $event->disputeId());
        $this->assertSame('RESOLVED', $event->disputeStatus());
        $this->assertSame('RESOLVED_BUYER_FAVOUR', $event->disputeOutcome());
        $this->assertSame('45.00', $event->amountValue());
        $this->assertSame('USD', $event->amountCurrencyCode());
    }

    public function testMapsBillingSubscriptionCreatedEvent(): void
    {
        $event = EventFactory::fromPayload([
            'id' => 'WH-8',
            'event_type' => 'BILLING.SUBSCRIPTION.CREATED',
            'resource' => [
                'id' => 'I-SUBSCRIPTION-1',
                'status' => 'APPROVAL_PENDING',
                'plan_id' => 'P-PLAN-1',
                'subscriber' => ['payer_id' => 'PAYER-1'],
            ],
        ]);

        $this->assertInstanceOf(BillingSubscriptionCreatedEvent::class, $event);
        $this->assertSame('I-SUBSCRIPTION-1', $event->subscriptionId());
        $this->assertSame('APPROVAL_PENDING', $event->subscriptionStatus());
        $this->assertSame('P-PLAN-1', $event->planId());
        $this->assertSame('PAYER-1', $event->payerId());
    }

    public function testMapsBillingSubscriptionCancelledEvent(): void
    {
        $event = EventFactory::fromPayload([
            'id' => 'WH-9',
            'event_type' => 'BILLING.SUBSCRIPTION.CANCELLED',
            'resource' => [
                'id' => 'I-SUBSCRIPTION-2',
                'status' => 'CANCELLED',
                'plan_id' => 'P-PLAN-2',
                'status_change_note' => 'Customer requested cancellation',
            ],
        ]);

        $this->assertInstanceOf(BillingSubscriptionCancelledEvent::class, $event);
        $this->assertSame('I-SUBSCRIPTION-2', $event->subscriptionId());
        $this->assertSame('CANCELLED', $event->subscriptionStatus());
        $this->assertSame('P-PLAN-2', $event->planId());
        $this->assertSame('Customer requested cancellation', $event->statusChangeNote());
    }

    public function testMapsCheckoutOrderCompletedEvent(): void
    {
        $event = EventFactory::fromPayload([
            'id' => 'WH-10',
            'event_type' => 'CHECKOUT.ORDER.COMPLETED',
            'resource' => [
                'id' => 'ORDER-999',
                'status' => 'COMPLETED',
                'payer' => ['payer_id' => 'PAYER-XYZ'],
                'purchase_units' => [
                    ['amount' => ['value' => '99.99', 'currency_code' => 'USD']],
                ],
            ],
        ]);

        $this->assertInstanceOf(CheckoutOrderCompletedEvent::class, $event);
        $this->assertSame('ORDER-999', $event->orderId());
        $this->assertSame('COMPLETED', $event->orderStatus());
        $this->assertSame('PAYER-XYZ', $event->payerId());
        $this->assertSame('99.99', $event->amountValue());
        $this->assertSame('USD', $event->amountCurrencyCode());
    }

    public function testMapsPaymentCapturePendingEvent(): void
    {
        $event = EventFactory::fromPayload([
            'id' => 'WH-11',
            'event_type' => 'PAYMENT.CAPTURE.PENDING',
            'resource' => [
                'id' => 'CAP-PENDING-1',
                'status' => 'PENDING',
                'amount' => ['value' => '22.50', 'currency_code' => 'USD'],
                'status_details' => ['reason' => 'PENDING_REVIEW'],
            ],
        ]);

        $this->assertInstanceOf(PaymentCapturePendingEvent::class, $event);
        $this->assertSame('CAP-PENDING-1', $event->captureId());
        $this->assertSame('PENDING', $event->captureStatus());
        $this->assertSame('22.50', $event->amountValue());
        $this->assertSame('USD', $event->amountCurrencyCode());
        $this->assertSame('PENDING_REVIEW', $event->statusReason());
    }

    public function testMapsPaymentCaptureReversedEvent(): void
    {
        $event = EventFactory::fromPayload([
            'id' => 'WH-12',
            'event_type' => 'PAYMENT.CAPTURE.REVERSED',
            'resource' => [
                'id' => 'CAP-REV-1',
                'status' => 'REVERSED',
                'amount' => ['value' => '50.00', 'currency_code' => 'USD'],
                'reason_code' => 'CHARGEBACK',
            ],
        ]);

        $this->assertInstanceOf(PaymentCaptureReversedEvent::class, $event);
        $this->assertSame('CAP-REV-1', $event->captureId());
        $this->assertSame('REVERSED', $event->captureStatus());
        $this->assertSame('50.00', $event->amountValue());
        $this->assertSame('USD', $event->amountCurrencyCode());
        $this->assertSame('CHARGEBACK', $event->reasonCode());
    }

    public function testMapsBillingSubscriptionLifecycleEvents(): void
    {
        $activated = EventFactory::fromPayload([
            'event_type' => 'BILLING.SUBSCRIPTION.ACTIVATED',
            'resource' => ['id' => 'I-1', 'status' => 'ACTIVE', 'plan_id' => 'P-1'],
        ]);
        $suspended = EventFactory::fromPayload([
            'event_type' => 'BILLING.SUBSCRIPTION.SUSPENDED',
            'resource' => [
                'id' => 'I-2',
                'status' => 'SUSPENDED',
                'plan_id' => 'P-2',
                'status_change_note' => 'Manual review',
            ],
        ]);
        $expired = EventFactory::fromPayload([
            'event_type' => 'BILLING.SUBSCRIPTION.EXPIRED',
            'resource' => ['id' => 'I-3', 'status' => 'EXPIRED', 'plan_id' => 'P-3'],
        ]);
        $paymentFailed = EventFactory::fromPayload([
            'event_type' => 'BILLING.SUBSCRIPTION.PAYMENT.FAILED',
            'resource' => [
                'id' => 'I-4',
                'status' => 'SUSPENDED',
                'billing_info' => [
                    'last_failed_payment' => [
                        'amount' => ['value' => '12.34', 'currency_code' => 'USD'],
                    ],
                ],
            ],
        ]);

        $this->assertInstanceOf(BillingSubscriptionActivatedEvent::class, $activated);
        $this->assertSame('I-1', $activated->subscriptionId());
        $this->assertInstanceOf(BillingSubscriptionSuspendedEvent::class, $suspended);
        $this->assertSame('Manual review', $suspended->statusChangeNote());
        $this->assertInstanceOf(BillingSubscriptionExpiredEvent::class, $expired);
        $this->assertSame('P-3', $expired->planId());
        $this->assertInstanceOf(BillingSubscriptionPaymentFailedEvent::class, $paymentFailed);
        $this->assertSame('12.34', $paymentFailed->failedPaymentAmountValue());
        $this->assertSame('USD', $paymentFailed->failedPaymentCurrencyCode());
    }

    public function testMapsPayoutEvents(): void
    {
        $batchSuccess = EventFactory::fromPayload([
            'event_type' => 'PAYMENT.PAYOUTSBATCH.SUCCESS',
            'resource' => [
                'batch_header' => [
                    'payout_batch_id' => 'BATCH-1',
                    'batch_status' => 'SUCCESS',
                ],
            ],
        ]);

        $itemSucceeded = EventFactory::fromPayload([
            'event_type' => 'PAYMENT.PAYOUTS-ITEM.SUCCEEDED',
            'resource' => [
                'payout_item' => [
                    'payout_item_id' => 'ITEM-1',
                    'payout_batch_id' => 'BATCH-1',
                    'transaction_status' => 'SUCCESS',
                ],
            ],
        ]);

        $itemDenied = EventFactory::fromPayload([
            'event_type' => 'PAYMENT.PAYOUTS-ITEM.DENIED',
            'resource' => [
                'payout_item' => [
                    'payout_item_id' => 'ITEM-2',
                    'payout_batch_id' => 'BATCH-1',
                    'transaction_status' => 'DENIED',
                ],
                'errors' => ['name' => 'RECEIVER_UNREGISTERED'],
            ],
        ]);

        $this->assertInstanceOf(PaymentPayoutsBatchSuccessEvent::class, $batchSuccess);
        $this->assertSame('BATCH-1', $batchSuccess->payoutBatchId());
        $this->assertSame('SUCCESS', $batchSuccess->batchStatus());
        $this->assertInstanceOf(PaymentPayoutsItemSucceededEvent::class, $itemSucceeded);
        $this->assertSame('ITEM-1', $itemSucceeded->payoutItemId());
        $this->assertSame('SUCCESS', $itemSucceeded->transactionStatus());
        $this->assertInstanceOf(PaymentPayoutsItemDeniedEvent::class, $itemDenied);
        $this->assertSame('ITEM-2', $itemDenied->payoutItemId());
        $this->assertSame('RECEIVER_UNREGISTERED', $itemDenied->errorsName());
    }
}
