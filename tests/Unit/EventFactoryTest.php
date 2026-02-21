<?php

declare(strict_types=1);

namespace Sujip\PayPal\Notifications\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Sujip\PayPal\Notifications\Webhook\Event\EventFactory;
use Sujip\PayPal\Notifications\Webhook\Event\UnknownWebhookEvent;

final class EventFactoryTest extends TestCase
{
    public function testReturnsUnknownWebhookEventEnvelope(): void
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

        $this->assertInstanceOf(UnknownWebhookEvent::class, $event);
        $this->assertSame('WH-1', $event->id());
        $this->assertSame('PAYMENT.CAPTURE.COMPLETED', $event->eventType());
        $this->assertSame('2026-01-01T00:00:00Z', $event->createTime());
        $this->assertSame('capture', $event->resourceType());
        $this->assertSame('Payment completed', $event->summary());
        $this->assertSame('CAPTURE-123', $event->resource()['id']);
    }
}
