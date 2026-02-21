<?php

declare(strict_types=1);

namespace Sujip\PayPal\Notifications\Tests\IntegrationLike;

use PHPUnit\Framework\TestCase;
use Sujip\PayPal\Notifications\Config\ClientConfig;
use Sujip\PayPal\Notifications\Config\Environment;
use Sujip\PayPal\Notifications\Exception\VerificationException;
use Sujip\PayPal\Notifications\PayPalClient;
use Sujip\PayPal\Notifications\Tests\Fakes\FakeTransport;
use Sujip\PayPal\Notifications\Webhook\Event\PaymentCaptureCompletedEvent;
use Sujip\PayPal\Notifications\Webhook\Event\PaymentCaptureRefundedEvent;
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
}
