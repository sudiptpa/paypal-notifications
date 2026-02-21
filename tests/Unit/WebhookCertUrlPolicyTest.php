<?php

declare(strict_types=1);

namespace Sujip\PayPal\Notifications\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Sujip\PayPal\Notifications\Config\ClientConfig;
use Sujip\PayPal\Notifications\Config\Environment;
use Sujip\PayPal\Notifications\Exception\VerificationException;
use Sujip\PayPal\Notifications\Webhook\WebhookCertUrlPolicy;

final class WebhookCertUrlPolicyTest extends TestCase
{
    public function testAcceptsTrustedHttpsCertUrl(): void
    {
        $policy = new WebhookCertUrlPolicy();
        $config = new ClientConfig('client', 'secret', 'wh', Environment::Sandbox);

        $policy->assertValid('https://api-m.sandbox.paypal.com/v1/notifications/certs/CERT-123', $config);

        $this->assertTrue(true);
    }

    public function testRejectsQueryStringInCertUrl(): void
    {
        $policy = new WebhookCertUrlPolicy();
        $config = new ClientConfig('client', 'secret', 'wh', Environment::Sandbox);

        $this->expectException(VerificationException::class);
        $policy->assertValid('https://api-m.sandbox.paypal.com/v1/notifications/certs/CERT-123?x=1', $config);
    }

    public function testRejectsNonDefaultHttpsPortWhenStrictPortEnabled(): void
    {
        $policy = new WebhookCertUrlPolicy();
        $config = new ClientConfig('client', 'secret', 'wh', Environment::Sandbox);

        $this->expectException(VerificationException::class);
        $policy->assertValid('https://api-m.sandbox.paypal.com:8443/v1/notifications/certs/CERT-123', $config);
    }
}
