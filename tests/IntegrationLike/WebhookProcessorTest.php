<?php

declare(strict_types=1);

namespace Sujip\PayPal\Notifications\Tests\IntegrationLike;

use PHPUnit\Framework\TestCase;
use Sujip\PayPal\Notifications\Adapter\ArrayWebhookRequestAdapter;
use Sujip\PayPal\Notifications\Config\ClientConfig;
use Sujip\PayPal\Notifications\Config\Environment;
use Sujip\PayPal\Notifications\Http\HttpResponse;
use Sujip\PayPal\Notifications\Idempotency\InMemoryIdempotencyStore;
use Sujip\PayPal\Notifications\Idempotency\WebhookIdempotencyGuard;
use Sujip\PayPal\Notifications\PayPalClient;
use Sujip\PayPal\Notifications\Tests\Fakes\InMemoryWebhookObserver;
use Sujip\PayPal\Notifications\Tests\Fakes\FakeTransport;
use Sujip\PayPal\Notifications\Webhook\WebhookEventRouter;

final class WebhookProcessorTest extends TestCase
{
    public function testProcessesVerifiedWebhookAndDispatchesHandler(): void
    {
        $transport = new FakeTransport([
            new HttpResponse(200, '{"access_token":"token-1","expires_in":3600}'),
            new HttpResponse(200, '{"verification_status":"SUCCESS"}'),
        ]);

        $client = new PayPalClient(
            config: new ClientConfig('client', 'secret', 'WH-ID-123', Environment::Sandbox),
            transport: $transport,
        );

        $handled = false;
        $router = (new WebhookEventRouter())->onCaptureCompleted(static function () use (&$handled): void {
            $handled = true;
        });

        $observer = new InMemoryWebhookObserver();
        $processor = $client->webhookProcessor($router, null, $observer);

        $result = $processor->process(new ArrayWebhookRequestAdapter(
            rawBody: '{"id":"WH-1","event_type":"PAYMENT.CAPTURE.COMPLETED","resource":{"id":"CAP-1"}}',
            headers: [
                'PAYPAL-TRANSMISSION-ID' => 'trans-1',
                'PAYPAL-TRANSMISSION-TIME' => (new \DateTimeImmutable('now'))->format(DATE_ATOM),
                'PAYPAL-TRANSMISSION-SIG' => 'sig',
                'PAYPAL-CERT-URL' => 'https://api-m.sandbox.paypal.com/v1/notifications/certs/CERT-123',
                'PAYPAL-AUTH-ALGO' => 'SHA256withRSA',
            ],
        ));

        $this->assertTrue($result->accepted);
        $this->assertTrue($result->dispatched);
        $this->assertFalse($result->duplicate);
        $this->assertSame('PAYMENT.CAPTURE.COMPLETED', $result->eventType);
        $this->assertTrue($handled);
        $this->assertCount(1, $observer->records);
    }

    public function testSkipsDuplicateWithIdempotencyGuard(): void
    {
        $transport = new FakeTransport([
            new HttpResponse(200, '{"access_token":"token-1","expires_in":3600}'),
            new HttpResponse(200, '{"verification_status":"SUCCESS"}'),
            new HttpResponse(200, '{"verification_status":"SUCCESS"}'),
        ]);

        $client = new PayPalClient(
            config: new ClientConfig('client', 'secret', 'WH-ID-123', Environment::Sandbox),
            transport: $transport,
        );

        $guard = new WebhookIdempotencyGuard(new InMemoryIdempotencyStore());
        $processor = $client->webhookProcessor(null, $guard);

        $adapter = new ArrayWebhookRequestAdapter(
            rawBody: '{"id":"WH-duplicate","event_type":"PAYMENT.CAPTURE.COMPLETED","resource":{"id":"CAP-1"}}',
            headers: [
                'PAYPAL-TRANSMISSION-ID' => 'trans-1',
                'PAYPAL-TRANSMISSION-TIME' => (new \DateTimeImmutable('now'))->format(DATE_ATOM),
                'PAYPAL-TRANSMISSION-SIG' => 'sig',
                'PAYPAL-CERT-URL' => 'https://api-m.sandbox.paypal.com/v1/notifications/certs/CERT-123',
                'PAYPAL-AUTH-ALGO' => 'SHA256withRSA',
            ],
        );

        $first = $processor->process($adapter);
        $second = $processor->process($adapter);

        $this->assertTrue($first->accepted);
        $this->assertFalse($first->duplicate);
        $this->assertTrue($second->accepted);
        $this->assertTrue($second->duplicate);
        $this->assertFalse($second->dispatched);
    }

    public function testReturnsRejectedResultWhenVerificationFails(): void
    {
        $transport = new FakeTransport([
            new HttpResponse(200, '{"access_token":"token-1","expires_in":3600}'),
            new HttpResponse(200, '{"verification_status":"FAILURE"}'),
        ]);

        $client = new PayPalClient(
            config: new ClientConfig('client', 'secret', 'WH-ID-123', Environment::Sandbox),
            transport: $transport,
        );

        $processor = $client->webhookProcessor();

        $result = $processor->process(new ArrayWebhookRequestAdapter(
            rawBody: '{"id":"WH-fail","event_type":"PAYMENT.CAPTURE.COMPLETED","resource":{"id":"CAP-1"}}',
            headers: [
                'PAYPAL-TRANSMISSION-ID' => 'trans-1',
                'PAYPAL-TRANSMISSION-TIME' => (new \DateTimeImmutable('now'))->format(DATE_ATOM),
                'PAYPAL-TRANSMISSION-SIG' => 'sig',
                'PAYPAL-CERT-URL' => 'https://api-m.sandbox.paypal.com/v1/notifications/certs/CERT-123',
                'PAYPAL-AUTH-ALGO' => 'SHA256withRSA',
            ],
        ));

        $this->assertFalse($result->accepted);
        $this->assertFalse($result->dispatched);
        $this->assertSame('PayPal signature verification failed.', $result->message);
    }
}
