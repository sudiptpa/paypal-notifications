<?php

declare(strict_types=1);

namespace Sujip\PayPal\Notifications\Tests\IntegrationLike;

use PHPUnit\Framework\TestCase;
use Sujip\PayPal\Notifications\Config\ClientConfig;
use Sujip\PayPal\Notifications\Config\Environment;
use Sujip\PayPal\Notifications\Http\HttpResponse;
use Sujip\PayPal\Notifications\InstantPaymentNotification\InstantPaymentNotificationStatus;
use Sujip\PayPal\Notifications\InstantPaymentNotification\VerifyInstantPaymentNotificationRequest;
use Sujip\PayPal\Notifications\PayPalClient;
use Sujip\PayPal\Notifications\Tests\Fakes\FakeTransport;

final class InstantPaymentNotificationResourceTest extends TestCase
{
    public function testReturnsVerified(): void
    {
        $transport = new FakeTransport([new HttpResponse(200, 'VERIFIED', [])]);
        $client = new PayPalClient(
            config: new ClientConfig('', '', '', Environment::Sandbox),
            transport: $transport,
        );

        $result = $client->instantPaymentNotification()->verify(
            VerifyInstantPaymentNotificationRequest::fromArray(['txn_id' => 'txn_123'])
        );

        $this->assertSame(InstantPaymentNotificationStatus::VERIFIED, $result->status);
        $this->assertTrue($result->isVerified());
        $this->assertStringContainsString('cmd=_notify-validate', $transport->requests[0]->body);
        $this->assertStringContainsString('txn_id=txn_123', $transport->requests[0]->body);
    }

    public function testReturnsInvalid(): void
    {
        $client = new PayPalClient(
            config: new ClientConfig('', '', '', Environment::Sandbox),
            transport: new FakeTransport([new HttpResponse(200, 'INVALID', [])]),
        );

        $result = $client->ipn()->verifyRaw('txn_id=txn_123');

        $this->assertSame(InstantPaymentNotificationStatus::INVALID, $result->status);
        $this->assertTrue($result->isInvalid());
    }

    public function testReturnsErrorOnUnexpectedResponse(): void
    {
        $client = new PayPalClient(
            config: new ClientConfig('', '', '', Environment::Sandbox),
            transport: new FakeTransport([new HttpResponse(200, 'UNKNOWN', [])]),
        );

        $result = $client->ipn()->verifyRaw('txn_id=txn_123');

        $this->assertSame(InstantPaymentNotificationStatus::ERROR, $result->status);
        $this->assertTrue($result->isError());
    }
}
