<?php

declare(strict_types=1);

namespace Sujip\PayPal\Notifications\Tests\IntegrationLike;

use PHPUnit\Framework\TestCase;
use Sujip\PayPal\Notifications\Adapter\ArrayWebhookRequestAdapter;
use Sujip\PayPal\Notifications\Config\ClientConfig;
use Sujip\PayPal\Notifications\Config\Environment;
use Sujip\PayPal\Notifications\Enum\VerificationStatus;
use Sujip\PayPal\Notifications\Auth\InMemoryTokenCache;
use Sujip\PayPal\Notifications\Auth\OAuthToken;
use Sujip\PayPal\Notifications\Exception\MalformedPayload;
use Sujip\PayPal\Notifications\Exception\SignatureVerificationFailed;
use Sujip\PayPal\Notifications\Exception\TransportFailed;
use Sujip\PayPal\Notifications\Exception\VerificationException;
use Sujip\PayPal\Notifications\Http\HttpResponse;
use Sujip\PayPal\Notifications\PayPalClient;
use Sujip\PayPal\Notifications\Tests\Fakes\FixedClock;
use Sujip\PayPal\Notifications\Tests\Fakes\FakeTransport;
use Sujip\PayPal\Notifications\Tests\Fakes\ThrowingTransport;
use Sujip\PayPal\Notifications\Webhook\VerifyWebhookSignatureRequest;

final class WebhooksResourceTest extends TestCase
{
    public function testVerifiesWebhookSignatureSuccess(): void
    {
        $transport = new FakeTransport([
            new HttpResponse(200, '{"access_token":"token-1","expires_in":3600}', []),
            new HttpResponse(200, '{"verification_status":"SUCCESS"}', ['Paypal-Debug-Id' => 'debug-123']),
        ]);

        $client = new PayPalClient(
            config: new ClientConfig('client', 'secret', 'WH-ID-123', Environment::Sandbox),
            transport: $transport,
            clock: new FixedClock(new \DateTimeImmutable('2026-01-01T00:00:10Z')),
        );

        $request = VerifyWebhookSignatureRequest::fromRawPayload(
            rawBody: '{"id":"evt_1","event_type":"PAYMENT.CAPTURE.COMPLETED"}',
            headers: [
                'PAYPAL-TRANSMISSION-ID' => 'trans-1',
                'PAYPAL-TRANSMISSION-TIME' => '2026-01-01T00:00:00Z',
                'PAYPAL-TRANSMISSION-SIG' => 'sig',
                'PAYPAL-CERT-URL' => 'https://api-m.sandbox.paypal.com/v1/notifications/certs/CERT-123',
                'PAYPAL-AUTH-ALGO' => 'SHA256withRSA',
            ],
        );

        $result = $client->webhooks()->verifySignature($request);

        $this->assertTrue($result->isSuccess());
        $this->assertSame(VerificationStatus::SUCCESS, $result->status);
        $this->assertSame('debug-123', $result->debugId);
    }

    public function testVerifiesWebhookSignatureFailure(): void
    {
        $transport = new FakeTransport([
            new HttpResponse(200, '{"access_token":"token-1","expires_in":3600}', []),
            new HttpResponse(200, '{"verification_status":"FAILURE"}', []),
        ]);

        $client = new PayPalClient(
            config: new ClientConfig('client', 'secret', 'WH-ID-123', Environment::Sandbox),
            transport: $transport,
            clock: new FixedClock(new \DateTimeImmutable('2026-01-01T00:00:10Z')),
        );

        $request = VerifyWebhookSignatureRequest::fromRawPayload(
            rawBody: '{"id":"evt_1","event_type":"PAYMENT.CAPTURE.COMPLETED"}',
            headers: [
                'PAYPAL-TRANSMISSION-ID' => 'trans-1',
                'PAYPAL-TRANSMISSION-TIME' => '2026-01-01T00:00:00Z',
                'PAYPAL-TRANSMISSION-SIG' => 'sig',
                'PAYPAL-CERT-URL' => 'https://api-m.sandbox.paypal.com/v1/notifications/certs/CERT-123',
                'PAYPAL-AUTH-ALGO' => 'SHA256withRSA',
            ],
        );

        $result = $client->webhooks()->verifySignature($request);

        $this->assertTrue($result->isFailure());
        $this->assertSame(VerificationStatus::FAILURE, $result->status);
    }

    public function testThrowsOnNonSuccessfulVerificationHttpStatus(): void
    {
        $transport = new FakeTransport([
            new HttpResponse(200, '{"access_token":"token-1","expires_in":3600}', []),
            new HttpResponse(422, '{"name":"UNPROCESSABLE_ENTITY"}', ['Paypal-Debug-Id' => 'debug-422']),
        ]);

        $client = new PayPalClient(
            config: new ClientConfig('client', 'secret', 'WH-ID-123', Environment::Sandbox),
            transport: $transport,
            clock: new FixedClock(new \DateTimeImmutable('2026-01-01T00:00:10Z')),
        );

        $request = VerifyWebhookSignatureRequest::fromRawPayload(
            rawBody: '{"id":"evt_1","event_type":"PAYMENT.CAPTURE.COMPLETED"}',
            headers: [
                'PAYPAL-TRANSMISSION-ID' => 'trans-1',
                'PAYPAL-TRANSMISSION-TIME' => '2026-01-01T00:00:00Z',
                'PAYPAL-TRANSMISSION-SIG' => 'sig',
                'PAYPAL-CERT-URL' => 'https://api-m.sandbox.paypal.com/v1/notifications/certs/CERT-123',
                'PAYPAL-AUTH-ALGO' => 'SHA256withRSA',
            ],
        );

        $this->expectException(VerificationException::class);
        $client->webhooks()->verifySignature($request);
    }

    public function testRetriesOnRetryableHttpStatusAndThenSucceeds(): void
    {
        $transport = new FakeTransport([
            new HttpResponse(200, '{"access_token":"token-1","expires_in":3600}', []),
            new HttpResponse(503, '{"name":"SERVICE_UNAVAILABLE"}', []),
            new HttpResponse(200, '{"verification_status":"SUCCESS"}', []),
        ]);

        $client = new PayPalClient(
            config: new ClientConfig(
                'client',
                'secret',
                'WH-ID-123',
                Environment::Sandbox,
                verificationMaxRetries: 1,
                verificationRetryBackoffMs: 0,
                verificationRetryMaxBackoffMs: 0,
            ),
            transport: $transport,
            clock: new FixedClock(new \DateTimeImmutable('2026-01-01T00:00:10Z')),
        );

        $request = VerifyWebhookSignatureRequest::fromRawPayload(
            rawBody: '{"id":"evt_1","event_type":"PAYMENT.CAPTURE.COMPLETED"}',
            headers: [
                'PAYPAL-TRANSMISSION-ID' => 'trans-1',
                'PAYPAL-TRANSMISSION-TIME' => '2026-01-01T00:00:00Z',
                'PAYPAL-TRANSMISSION-SIG' => 'sig',
                'PAYPAL-CERT-URL' => 'https://api-m.sandbox.paypal.com/v1/notifications/certs/CERT-123',
                'PAYPAL-AUTH-ALGO' => 'SHA256withRSA',
            ],
        );

        $result = $client->webhooks()->verifySignature($request);

        $this->assertTrue($result->isSuccess());
        $this->assertCount(3, $transport->requests);
    }

    public function testThrowsSignatureVerificationFailedOnNonRetryableHttpFailure(): void
    {
        $transport = new FakeTransport([
            new HttpResponse(200, '{"access_token":"token-1","expires_in":3600}', []),
            new HttpResponse(422, '{"name":"UNPROCESSABLE_ENTITY"}', []),
        ]);

        $client = new PayPalClient(
            config: new ClientConfig('client', 'secret', 'WH-ID-123', Environment::Sandbox),
            transport: $transport,
            clock: new FixedClock(new \DateTimeImmutable('2026-01-01T00:00:10Z')),
        );

        $request = VerifyWebhookSignatureRequest::fromRawPayload(
            rawBody: '{"id":"evt_1","event_type":"PAYMENT.CAPTURE.COMPLETED"}',
            headers: [
                'PAYPAL-TRANSMISSION-ID' => 'trans-1',
                'PAYPAL-TRANSMISSION-TIME' => '2026-01-01T00:00:00Z',
                'PAYPAL-TRANSMISSION-SIG' => 'sig',
                'PAYPAL-CERT-URL' => 'https://api-m.sandbox.paypal.com/v1/notifications/certs/CERT-123',
                'PAYPAL-AUTH-ALGO' => 'SHA256withRSA',
            ],
        );

        $this->expectException(SignatureVerificationFailed::class);
        $client->webhooks()->verifySignature($request);
    }

    public function testThrowsTransportFailedAfterRetriesExhausted(): void
    {
        $tokenCache = new InMemoryTokenCache();
        $tokenCache->put(
            hash('sha256', Environment::Sandbox->name.'|client'),
            new OAuthToken('cached-token', new \DateTimeImmutable('+15 minutes'))
        );

        $client = new PayPalClient(
            config: new ClientConfig(
                'client',
                'secret',
                'WH-ID-123',
                Environment::Sandbox,
                verificationMaxRetries: 1,
                verificationRetryBackoffMs: 0,
                verificationRetryMaxBackoffMs: 0,
            ),
            transport: new ThrowingTransport(),
            clock: new FixedClock(new \DateTimeImmutable('2026-01-01T00:00:10Z')),
            tokenCache: $tokenCache,
        );

        $request = VerifyWebhookSignatureRequest::fromRawPayload(
            rawBody: '{"id":"evt_1","event_type":"PAYMENT.CAPTURE.COMPLETED"}',
            headers: [
                'PAYPAL-TRANSMISSION-ID' => 'trans-1',
                'PAYPAL-TRANSMISSION-TIME' => '2026-01-01T00:00:00Z',
                'PAYPAL-TRANSMISSION-SIG' => 'sig',
                'PAYPAL-CERT-URL' => 'https://api-m.sandbox.paypal.com/v1/notifications/certs/CERT-123',
                'PAYPAL-AUTH-ALGO' => 'SHA256withRSA',
            ],
        );

        $this->expectException(TransportFailed::class);
        $client->webhooks()->verifySignature($request);
    }

    public function testThrowsMalformedPayloadWhenVerificationResponseIsInvalidJson(): void
    {
        $transport = new FakeTransport([
            new HttpResponse(200, '{"access_token":"token-1","expires_in":3600}', []),
            new HttpResponse(200, '{invalid-json', []),
        ]);

        $client = new PayPalClient(
            config: new ClientConfig('client', 'secret', 'WH-ID-123', Environment::Sandbox),
            transport: $transport,
            clock: new FixedClock(new \DateTimeImmutable('2026-01-01T00:00:10Z')),
        );

        $request = VerifyWebhookSignatureRequest::fromRawPayload(
            rawBody: '{"id":"evt_1","event_type":"PAYMENT.CAPTURE.COMPLETED"}',
            headers: [
                'PAYPAL-TRANSMISSION-ID' => 'trans-1',
                'PAYPAL-TRANSMISSION-TIME' => '2026-01-01T00:00:00Z',
                'PAYPAL-TRANSMISSION-SIG' => 'sig',
                'PAYPAL-CERT-URL' => 'https://api-m.sandbox.paypal.com/v1/notifications/certs/CERT-123',
                'PAYPAL-AUTH-ALGO' => 'SHA256withRSA',
            ],
        );

        $this->expectException(MalformedPayload::class);
        $client->webhooks()->verifySignature($request);
    }

    public function testRejectsWebhookOutsideReplayWindow(): void
    {
        $transport = new FakeTransport([
            new HttpResponse(200, '{"access_token":"token-1","expires_in":3600}', []),
        ]);

        $client = new PayPalClient(
            config: new ClientConfig(
                'client',
                'secret',
                'WH-ID-123',
                Environment::Sandbox,
                timeoutSeconds: 10,
                maxWebhookTransmissionAgeSeconds: 300,
                allowedWebhookClockSkewSeconds: 30,
            ),
            transport: $transport,
            clock: new FixedClock(new \DateTimeImmutable('2026-01-01T00:15:00Z')),
        );

        $request = VerifyWebhookSignatureRequest::fromRawPayload(
            rawBody: '{"id":"evt_1","event_type":"PAYMENT.CAPTURE.COMPLETED"}',
            headers: [
                'PAYPAL-TRANSMISSION-ID' => 'trans-1',
                'PAYPAL-TRANSMISSION-TIME' => '2026-01-01T00:00:00Z',
                'PAYPAL-TRANSMISSION-SIG' => 'sig',
                'PAYPAL-CERT-URL' => 'https://api-m.sandbox.paypal.com/v1/notifications/certs/CERT-123',
                'PAYPAL-AUTH-ALGO' => 'SHA256withRSA',
            ],
        );

        $this->expectException(VerificationException::class);
        $client->webhooks()->verifySignature($request);
    }

    public function testBuildsRequestFromRawPayloadUsingConfigStrictCertSetting(): void
    {
        $client = new PayPalClient(
            config: new ClientConfig(
                'client',
                'secret',
                'WH-ID-123',
                Environment::Sandbox,
                strictPayPalCertUrlValidation: false,
            ),
            transport: new FakeTransport([]),
        );

        $request = $client->webhooks()->requestFromRawPayload(
            rawBody: '{"id":"evt_1","event_type":"PAYMENT.CAPTURE.COMPLETED"}',
            headers: [
                'PAYPAL-TRANSMISSION-ID' => 'trans-1',
                'PAYPAL-TRANSMISSION-TIME' => '2026-01-01T00:00:00Z',
                'PAYPAL-TRANSMISSION-SIG' => 'sig',
                'PAYPAL-CERT-URL' => 'https://example.com/not-paypal-cert',
                'PAYPAL-AUTH-ALGO' => 'SHA256withRSA',
            ],
        );

        $this->assertFalse($request->strictPayPalCertUrlValidation);
    }

    public function testBuildsRequestFromAdapter(): void
    {
        $client = new PayPalClient(
            config: new ClientConfig('client', 'secret', 'WH-ID-123', Environment::Sandbox),
            transport: new FakeTransport([]),
        );

        $request = $client->webhooks()->requestFromAdapter(new ArrayWebhookRequestAdapter(
            rawBody: '{"id":"evt_1","event_type":"PAYMENT.CAPTURE.COMPLETED"}',
            headers: [
                'PAYPAL-TRANSMISSION-ID' => 'trans-1',
                'PAYPAL-TRANSMISSION-TIME' => '2026-01-01T00:00:00Z',
                'PAYPAL-TRANSMISSION-SIG' => 'sig',
                'PAYPAL-CERT-URL' => 'https://api-m.sandbox.paypal.com/v1/notifications/certs/CERT-123',
                'PAYPAL-AUTH-ALGO' => 'SHA256withRSA',
            ],
            webhookId: 'WH-OVERRIDE-1',
        ));

        $this->assertSame('WH-OVERRIDE-1', $request->webhookId);
        $this->assertSame('trans-1', $request->transmissionId);
    }

    public function testRejectsCertUrlOutsideExpectedCertPathPrefix(): void
    {
        $transport = new FakeTransport([
            new HttpResponse(200, '{"access_token":"token-1","expires_in":3600}', []),
        ]);

        $client = new PayPalClient(
            config: new ClientConfig('client', 'secret', 'WH-ID-123', Environment::Sandbox),
            transport: $transport,
            clock: new FixedClock(new \DateTimeImmutable('2026-01-01T00:00:10Z')),
        );

        $request = VerifyWebhookSignatureRequest::fromRawPayload(
            rawBody: '{"id":"evt_1","event_type":"PAYMENT.CAPTURE.COMPLETED"}',
            headers: [
                'PAYPAL-TRANSMISSION-ID' => 'trans-1',
                'PAYPAL-TRANSMISSION-TIME' => '2026-01-01T00:00:00Z',
                'PAYPAL-TRANSMISSION-SIG' => 'sig',
                'PAYPAL-CERT-URL' => 'https://api-m.sandbox.paypal.com/v1/notifications/not-certs/CERT-123',
                'PAYPAL-AUTH-ALGO' => 'SHA256withRSA',
            ],
        );

        $this->expectException(VerificationException::class);
        $client->webhooks()->verifySignature($request);
    }
}
