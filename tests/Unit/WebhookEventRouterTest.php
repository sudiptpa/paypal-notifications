<?php

declare(strict_types=1);

namespace Sujip\PayPal\Notifications\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Sujip\PayPal\Notifications\Webhook\Event\EventFactory;
use Sujip\PayPal\Notifications\Webhook\Event\WebhookEventType;
use Sujip\PayPal\Notifications\Webhook\WebhookEventRouter;

final class WebhookEventRouterTest extends TestCase
{
    public function testDispatchesRegisteredTypedHandler(): void
    {
        $event = EventFactory::fromPayload([
            'id' => 'WH-100',
            'event_type' => 'PAYMENT.CAPTURE.COMPLETED',
            'resource' => ['id' => 'CAP-100', 'status' => 'COMPLETED'],
        ]);

        $router = (new WebhookEventRouter())
            ->on('PAYMENT.CAPTURE.COMPLETED', static function ($event): string {
                return 'capture:'.$event->id();
            })
            ->fallback(static fn (): string => 'fallback');

        $result = $router->dispatch($event);

        $this->assertSame('capture:WH-100', $result);
    }

    public function testDispatchesFallbackForUnknownEvent(): void
    {
        $event = EventFactory::fromPayload([
            'id' => 'WH-200',
            'event_type' => 'UNKNOWN.CUSTOM.EVENT',
            'resource' => ['foo' => 'bar'],
        ]);

        $router = (new WebhookEventRouter())
            ->on('PAYMENT.CAPTURE.COMPLETED', static fn (): string => 'capture')
            ->fallback(static function ($event): string {
                return 'fallback:'.$event->eventType();
            });

        $result = $router->dispatch($event);

        $this->assertSame('fallback:UNKNOWN.CUSTOM.EVENT', $result);
    }

    public function testReturnsNullWhenNoHandlerMatchesAndNoFallback(): void
    {
        $event = EventFactory::fromPayload([
            'id' => 'WH-300',
            'event_type' => 'NO_HANDLER.EVENT',
        ]);

        $router = new WebhookEventRouter();

        $this->assertNull($router->dispatch($event));
    }

    public function testDispatchesUsingEnumRegistration(): void
    {
        $event = EventFactory::fromPayload([
            'id' => 'WH-400',
            'event_type' => 'CUSTOMER.DISPUTE.CREATED',
            'resource' => ['dispute_id' => 'PP-D-400'],
        ]);

        $result = (new WebhookEventRouter())
            ->onType(WebhookEventType::CustomerDisputeCreated, static fn ($event): string => 'dispute:'.$event->id())
            ->dispatch($event);

        $this->assertSame('dispute:WH-400', $result);
    }

    public function testDispatchesUsingConvenienceMethods(): void
    {
        $event = EventFactory::fromPayload([
            'id' => 'WH-500',
            'event_type' => 'PAYMENT.CAPTURE.REFUNDED',
            'resource' => ['id' => 'REF-500'],
        ]);

        $result = (new WebhookEventRouter())
            ->onCaptureRefunded(static fn ($event): string => 'refund:'.$event->id())
            ->dispatch($event);

        $this->assertSame('refund:WH-500', $result);
    }
}
