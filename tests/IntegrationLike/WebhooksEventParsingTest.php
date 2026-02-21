<?php

declare(strict_types=1);

namespace Sujip\PayPal\Notifications\Tests\IntegrationLike;

use PHPUnit\Framework\TestCase;
use Sujip\PayPal\Notifications\Config\ClientConfig;
use Sujip\PayPal\Notifications\Config\Environment;
use Sujip\PayPal\Notifications\Exception\VerificationException;
use Sujip\PayPal\Notifications\PayPalClient;
use Sujip\PayPal\Notifications\Tests\Fakes\FakeTransport;
use Sujip\PayPal\Notifications\Webhook\Event\BillingSubscriptionPaymentFailedEvent;
use Sujip\PayPal\Notifications\Webhook\Event\BillingSubscriptionCancelledEvent;
use Sujip\PayPal\Notifications\Webhook\Event\CustomerDisputeCreatedEvent;
use Sujip\PayPal\Notifications\Webhook\Event\PaymentCaptureCompletedEvent;
use Sujip\PayPal\Notifications\Webhook\Event\PaymentCaptureRefundedEvent;
use Sujip\PayPal\Notifications\Webhook\Event\PaymentPayoutsItemDeniedEvent;
use Sujip\PayPal\Notifications\Webhook\Event\UnknownWebhookEvent;

final class WebhooksEventParsingTest extends TestCase
{
    public function testParsesRawEventIntoTypedEnvelope(): void
    {
        $client = new PayPalClient(
            config: new ClientConfig('client', 'secret', 'WH-ID', Environment::Sandbox),
            transport: new FakeTransport([]),
        );

        $event = $client->webhooks()->parseRawEvent(
            '{"id":"WH-1","event_type":"CUSTOM.EVENT","resource":{"foo":"bar"}}'
        );

        $this->assertInstanceOf(UnknownWebhookEvent::class, $event);
        $this->assertSame('WH-1', $event->id());
        $this->assertSame('CUSTOM.EVENT', $event->eventType());
        $this->assertSame('bar', $event->resource()['foo']);
    }

    public function testParsesMappedEventIntoTypedModel(): void
    {
        $client = new PayPalClient(
            config: new ClientConfig('client', 'secret', 'WH-ID', Environment::Sandbox),
            transport: new FakeTransport([]),
        );

        $event = $client->webhooks()->parseRawEvent(
            '{"id":"WH-9","event_type":"PAYMENT.CAPTURE.COMPLETED","resource":{"id":"CAPTURE-999","status":"COMPLETED","amount":{"value":"25.00","currency_code":"USD"}}}'
        );

        $this->assertInstanceOf(PaymentCaptureCompletedEvent::class, $event);
        $this->assertSame('CAPTURE-999', $event->captureId());
        $this->assertSame('COMPLETED', $event->captureStatus());
        $this->assertSame('25.00', $event->amountValue());
    }

    public function testParsesRefundMappedEventIntoTypedModel(): void
    {
        $client = new PayPalClient(
            config: new ClientConfig('client', 'secret', 'WH-ID', Environment::Sandbox),
            transport: new FakeTransport([]),
        );

        $event = $client->webhooks()->parseRawEvent(
            '{"id":"WH-10","event_type":"PAYMENT.CAPTURE.REFUNDED","resource":{"id":"REFUND-999","status":"COMPLETED","amount":{"value":"7.00","currency_code":"USD"},"invoice_id":"INV-REF-1"}}'
        );

        $this->assertInstanceOf(PaymentCaptureRefundedEvent::class, $event);
        $this->assertSame('REFUND-999', $event->refundId());
        $this->assertSame('COMPLETED', $event->refundStatus());
        $this->assertSame('7.00', $event->amountValue());
        $this->assertSame('USD', $event->amountCurrencyCode());
        $this->assertSame('INV-REF-1', $event->invoiceId());
    }

    public function testThrowsOnInvalidRawEventJson(): void
    {
        $client = new PayPalClient(
            config: new ClientConfig('client', 'secret', 'WH-ID', Environment::Sandbox),
            transport: new FakeTransport([]),
        );

        $this->expectException(VerificationException::class);
        $client->webhooks()->parseRawEvent('invalid-json');
    }

    public function testParsesCustomerDisputeCreatedMappedEventIntoTypedModel(): void
    {
        $client = new PayPalClient(
            config: new ClientConfig('client', 'secret', 'WH-ID', Environment::Sandbox),
            transport: new FakeTransport([]),
        );

        $event = $client->webhooks()->parseRawEvent(
            '{"id":"WH-11","event_type":"CUSTOMER.DISPUTE.CREATED","resource":{"dispute_id":"PP-D-123","status":"OPEN","reason":"MERCHANDISE_OR_SERVICE_NOT_RECEIVED","dispute_amount":{"value":"12.00","currency_code":"USD"}}}'
        );

        $this->assertInstanceOf(CustomerDisputeCreatedEvent::class, $event);
        $this->assertSame('PP-D-123', $event->disputeId());
        $this->assertSame('OPEN', $event->disputeStatus());
        $this->assertSame('12.00', $event->amountValue());
    }

    public function testParsesBillingSubscriptionCancelledMappedEventIntoTypedModel(): void
    {
        $client = new PayPalClient(
            config: new ClientConfig('client', 'secret', 'WH-ID', Environment::Sandbox),
            transport: new FakeTransport([]),
        );

        $event = $client->webhooks()->parseRawEvent(
            '{"id":"WH-12","event_type":"BILLING.SUBSCRIPTION.CANCELLED","resource":{"id":"I-SUB-99","status":"CANCELLED","plan_id":"P-99","status_change_note":"Cancelled by merchant"}}'
        );

        $this->assertInstanceOf(BillingSubscriptionCancelledEvent::class, $event);
        $this->assertSame('I-SUB-99', $event->subscriptionId());
        $this->assertSame('CANCELLED', $event->subscriptionStatus());
        $this->assertSame('P-99', $event->planId());
    }

    public function testParsesBillingSubscriptionPaymentFailedMappedEventIntoTypedModel(): void
    {
        $client = new PayPalClient(
            config: new ClientConfig('client', 'secret', 'WH-ID', Environment::Sandbox),
            transport: new FakeTransport([]),
        );

        $event = $client->webhooks()->parseRawEvent(
            '{"id":"WH-13","event_type":"BILLING.SUBSCRIPTION.PAYMENT.FAILED","resource":{"id":"I-SUB-13","status":"SUSPENDED","billing_info":{"last_failed_payment":{"amount":{"value":"11.00","currency_code":"USD"}}}}}'
        );

        $this->assertInstanceOf(BillingSubscriptionPaymentFailedEvent::class, $event);
        $this->assertSame('I-SUB-13', $event->subscriptionId());
        $this->assertSame('11.00', $event->failedPaymentAmountValue());
    }

    public function testParsesPayoutDeniedMappedEventIntoTypedModel(): void
    {
        $client = new PayPalClient(
            config: new ClientConfig('client', 'secret', 'WH-ID', Environment::Sandbox),
            transport: new FakeTransport([]),
        );

        $event = $client->webhooks()->parseRawEvent(
            '{"id":"WH-14","event_type":"PAYMENT.PAYOUTS-ITEM.DENIED","resource":{"payout_item":{"payout_item_id":"ITEM-14","payout_batch_id":"BATCH-14","transaction_status":"DENIED"},"errors":{"name":"RECEIVER_UNREGISTERED"}}}'
        );

        $this->assertInstanceOf(PaymentPayoutsItemDeniedEvent::class, $event);
        $this->assertSame('ITEM-14', $event->payoutItemId());
        $this->assertSame('DENIED', $event->transactionStatus());
        $this->assertSame('RECEIVER_UNREGISTERED', $event->errorsName());
    }
}
