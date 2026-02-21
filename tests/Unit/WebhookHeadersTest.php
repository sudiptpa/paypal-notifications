<?php

declare(strict_types=1);

namespace Sujip\PayPal\Notifications\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Sujip\PayPal\Notifications\Exception\InvalidWebhookHeadersException;
use Sujip\PayPal\Notifications\Webhook\WebhookHeaders;

final class WebhookHeadersTest extends TestCase
{
    public function testExtractsCaseInsensitiveHeaders(): void
    {
        $headers = WebhookHeaders::fromArray([
            'HTTP_PAYPAL_TRANSMISSION_ID' => 'abc-id',
            'paypal-transmission-time' => '2026-01-01T00:00:00Z',
            'PayPal-Transmission-Sig' => 'abc-signature',
            'PAYPAL_CERT_URL' => 'https://api-m.paypal.com/certs/cert.pem',
            'paypal-auth-algo' => 'SHA256withRSA',
        ]);

        $this->assertSame('abc-id', $headers->transmissionId());
        $this->assertSame('2026-01-01T00:00:00Z', $headers->transmissionTime());
        $this->assertSame('abc-signature', $headers->transmissionSig());
        $this->assertSame('https://api-m.paypal.com/certs/cert.pem', $headers->certUrl());
        $this->assertSame('SHA256withRSA', $headers->authAlgo());
    }

    public function testThrowsWhenHeaderMissing(): void
    {
        $headers = WebhookHeaders::fromArray([]);

        $this->expectException(InvalidWebhookHeadersException::class);
        $headers->transmissionId();
    }
}
