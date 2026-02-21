<?php

declare(strict_types=1);

namespace Sujip\PayPal\Notifications\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Sujip\PayPal\Notifications\Exception\InvalidPayloadException;
use Sujip\PayPal\Notifications\Exception\VerificationException;
use Sujip\PayPal\Notifications\Webhook\VerifyWebhookSignatureRequest;

final class VerifyWebhookSignatureRequestTest extends TestCase
{
    public function testCreatesFromRawPayload(): void
    {
        $raw = '{"id":"evt_123","event_type":"PAYMENT.CAPTURE.COMPLETED"}';
        $request = VerifyWebhookSignatureRequest::fromRawPayload($raw, [
            'PAYPAL-TRANSMISSION-ID' => 'trans-1',
            'PAYPAL-TRANSMISSION-TIME' => '2026-01-01T00:00:00Z',
            'PAYPAL-TRANSMISSION-SIG' => 'sig',
            'PAYPAL-CERT-URL' => 'https://api-m.sandbox.paypal.com/v1/notifications/certs/CERT-123',
            'PAYPAL-AUTH-ALGO' => 'SHA256withRSA',
        ]);

        $this->assertSame('trans-1', $request->transmissionId);
        $this->assertSame('PAYMENT.CAPTURE.COMPLETED', $request->webhookEvent['event_type']);
    }

    public function testThrowsOnInvalidJson(): void
    {
        $this->expectException(InvalidPayloadException::class);

        VerifyWebhookSignatureRequest::fromRawPayload('not-json', [
            'PAYPAL-TRANSMISSION-ID' => 'trans-1',
            'PAYPAL-TRANSMISSION-TIME' => '2026-01-01T00:00:00Z',
            'PAYPAL-TRANSMISSION-SIG' => 'sig',
            'PAYPAL-CERT-URL' => 'https://api-m.sandbox.paypal.com/v1/notifications/certs/CERT-123',
            'PAYPAL-AUTH-ALGO' => 'SHA256withRSA',
        ]);
    }

    public function testThrowsWhenCertUrlIsNotPayPalDomain(): void
    {
        $this->expectException(InvalidPayloadException::class);

        VerifyWebhookSignatureRequest::fromRawPayload('{\"id\":\"evt_123\"}', [
            'PAYPAL-TRANSMISSION-ID' => 'trans-1',
            'PAYPAL-TRANSMISSION-TIME' => '2026-01-01T00:00:00Z',
            'PAYPAL-TRANSMISSION-SIG' => 'sig',
            'PAYPAL-CERT-URL' => 'https://attacker.example.com/cert.pem',
            'PAYPAL-AUTH-ALGO' => 'SHA256withRSA',
        ]);
    }

    public function testCanDisableStrictCertUrlValidation(): void
    {
        $request = VerifyWebhookSignatureRequest::fromRawPayload(
            rawBody: '{"id":"evt_123","event_type":"PAYMENT.CAPTURE.COMPLETED"}',
            headers: [
                'PAYPAL-TRANSMISSION-ID' => 'trans-1',
                'PAYPAL-TRANSMISSION-TIME' => '2026-01-01T00:00:00Z',
                'PAYPAL-TRANSMISSION-SIG' => 'sig',
                'PAYPAL-CERT-URL' => 'https://attacker.example.com/cert.pem',
                'PAYPAL-AUTH-ALGO' => 'SHA256withRSA',
            ],
            strictPayPalCertUrlValidation: false,
        );

        $this->assertFalse($request->strictPayPalCertUrlValidation);
    }

    public function testThrowsWhenTransmissionTimeIsTooOld(): void
    {
        $request = VerifyWebhookSignatureRequest::fromRawPayload('{"id":"evt_123"}', [
            'PAYPAL-TRANSMISSION-ID' => 'trans-1',
            'PAYPAL-TRANSMISSION-TIME' => '2020-01-01T00:00:00Z',
            'PAYPAL-TRANSMISSION-SIG' => 'sig',
            'PAYPAL-CERT-URL' => 'https://api-m.sandbox.paypal.com/v1/notifications/certs/CERT-123',
            'PAYPAL-AUTH-ALGO' => 'SHA256withRSA',
        ]);

        $this->expectException(VerificationException::class);
        $request->assertTransmissionTimeWithin(new \DateTimeImmutable('2026-01-01T00:00:00Z'), 300, 30);
    }
}
