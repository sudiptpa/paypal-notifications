<?php

declare(strict_types=1);

namespace Sujip\PayPal\Notifications\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Sujip\PayPal\Notifications\Idempotency\InMemoryIdempotencyStore;
use Sujip\PayPal\Notifications\Idempotency\WebhookIdempotencyGuard;
use Sujip\PayPal\Notifications\Webhook\Event\EventFactory;

final class WebhookIdempotencyGuardTest extends TestCase
{
    public function testAllowsFirstProcessingThenBlocksDuplicate(): void
    {
        $event = EventFactory::fromPayload([
            'id' => 'WH-DUP-1',
            'event_type' => 'PAYMENT.CAPTURE.COMPLETED',
        ]);

        $guard = new WebhookIdempotencyGuard(new InMemoryIdempotencyStore());

        $this->assertTrue($guard->checkAndRemember($event));
        $this->assertFalse($guard->checkAndRemember($event));
    }

    public function testRejectsEventWithoutId(): void
    {
        $event = EventFactory::fromPayload([
            'event_type' => 'PAYMENT.CAPTURE.COMPLETED',
        ]);

        $guard = new WebhookIdempotencyGuard(new InMemoryIdempotencyStore());

        $this->assertFalse($guard->checkAndRemember($event));
    }
}
