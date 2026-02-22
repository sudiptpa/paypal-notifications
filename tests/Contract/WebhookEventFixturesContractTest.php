<?php

declare(strict_types=1);

namespace Sujip\PayPal\Notifications\Tests\Contract;

use PHPUnit\Framework\TestCase;
use Sujip\PayPal\Notifications\Config\ClientConfig;
use Sujip\PayPal\Notifications\Config\Environment;
use Sujip\PayPal\Notifications\PayPalClient;
use Sujip\PayPal\Notifications\Tests\Fakes\FakeTransport;

final class WebhookEventFixturesContractTest extends TestCase
{
    /**
     * @return iterable<string, array{file: string, event_type: string, class: class-string}>
     */
    public static function fixtureProvider(): iterable
    {
        /** @var array<int, array{file: string, event_type: string, class: class-string}> $fixtures */
        $fixtures = require dirname(__DIR__).'/Fixtures/webhooks/fixtures.php';

        foreach ($fixtures as $fixture) {
            yield $fixture['file'] => $fixture;
        }
    }

    /**
     * @dataProvider fixtureProvider
     * @param array{file: string, event_type: string, class: class-string} $fixture
     */
    public function testFixtureMapsToExpectedTypedEvent(array $fixture): void
    {
        $path = dirname(__DIR__).'/Fixtures/webhooks/'.$fixture['file'];
        $raw = file_get_contents($path);
        $this->assertIsString($raw);

        $client = new PayPalClient(
            config: new ClientConfig('client', 'secret', 'WH-ID', Environment::Sandbox),
            transport: new FakeTransport([]),
        );

        $event = $client->webhooks()->parseRawEvent($raw);

        $this->assertInstanceOf($fixture['class'], $event);
        $this->assertSame($fixture['event_type'], $event->eventType());
    }
}

