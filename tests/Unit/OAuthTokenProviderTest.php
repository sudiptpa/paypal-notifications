<?php

declare(strict_types=1);

namespace Sujip\PayPal\Notifications\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Sujip\PayPal\Notifications\Auth\OAuthTokenProvider;
use Sujip\PayPal\Notifications\Config\ClientConfig;
use Sujip\PayPal\Notifications\Config\Environment;
use Sujip\PayPal\Notifications\Exception\AuthenticationException;
use Sujip\PayPal\Notifications\Http\HttpResponse;
use Sujip\PayPal\Notifications\Log\NullLogger;
use Sujip\PayPal\Notifications\Tests\Fakes\FakeTransport;
use Sujip\PayPal\Notifications\Tests\Fakes\FixedClock;
use Sujip\PayPal\Notifications\Tests\Fakes\ThrowingTransport;

final class OAuthTokenProviderTest extends TestCase
{
    public function testCachesTokenInMemory(): void
    {
        $transport = new FakeTransport([
            new HttpResponse(200, '{"access_token":"token-1","expires_in":3600}', []),
        ]);

        $provider = new OAuthTokenProvider(
            new ClientConfig('client', 'secret', 'webhook-id', Environment::Sandbox),
            $transport,
            new FixedClock(new \DateTimeImmutable('2026-01-01T00:00:00Z')),
            new NullLogger(),
        );

        $first = $provider->token();
        $second = $provider->token();

        $this->assertSame('token-1', $first);
        $this->assertSame('token-1', $second);
        $this->assertCount(1, $transport->requests);
    }

    public function testThrowsAuthenticationExceptionOnTransportFailure(): void
    {
        $provider = new OAuthTokenProvider(
            new ClientConfig('client', 'secret', 'webhook-id', Environment::Sandbox),
            new ThrowingTransport(),
            new FixedClock(new \DateTimeImmutable('2026-01-01T00:00:00Z')),
            new NullLogger(),
        );

        $this->expectException(AuthenticationException::class);
        $provider->token();
    }
}
