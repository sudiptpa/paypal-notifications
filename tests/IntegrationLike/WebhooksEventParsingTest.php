<?php

declare(strict_types=1);

namespace Sujip\PayPal\Notifications\Tests\IntegrationLike;

use PHPUnit\Framework\TestCase;
use Sujip\PayPal\Notifications\Config\ClientConfig;
use Sujip\PayPal\Notifications\Config\Environment;
use Sujip\PayPal\Notifications\Exception\VerificationException;
use Sujip\PayPal\Notifications\PayPalClient;
use Sujip\PayPal\Notifications\Tests\Fakes\FakeTransport;
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
