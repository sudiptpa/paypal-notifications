<?php

declare(strict_types=1);

namespace Sujip\PayPal\Notifications\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Sujip\PayPal\Notifications\Webhook\Event\CheckoutOrderApprovedEvent;
use Sujip\PayPal\Notifications\Webhook\Event\EventFactory;
use Sujip\PayPal\Notifications\Webhook\Event\PaymentCaptureCompletedEvent;
use Sujip\PayPal\Notifications\Webhook\Event\PaymentCaptureDeniedEvent;
use Sujip\PayPal\Notifications\Webhook\Event\PaymentCaptureRefundedEvent;
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
}
