<?php

declare(strict_types=1);

namespace Sujip\PayPal\Notifications\Resource;

use Sujip\PayPal\Notifications\Auth\OAuthTokenProvider;
use Sujip\PayPal\Notifications\Config\ClientConfig;
use Sujip\PayPal\Notifications\Contracts\ClockInterface;
use Sujip\PayPal\Notifications\Contracts\LoggerInterface;
use Sujip\PayPal\Notifications\Contracts\TransportInterface;
use Sujip\PayPal\Notifications\Enum\VerificationStatus;
use Sujip\PayPal\Notifications\Exception\VerificationException;
use Sujip\PayPal\Notifications\Http\HttpRequest;
use Sujip\PayPal\Notifications\Webhook\Event\EventFactory;
use Sujip\PayPal\Notifications\Webhook\Event\WebhookEventInterface;
use Sujip\PayPal\Notifications\Webhook\VerifyWebhookSignatureRequest;
use Sujip\PayPal\Notifications\Webhook\VerifyWebhookSignatureResult;

final class WebhooksResource
{
    public function __construct(
        private readonly ClientConfig $config,
        private readonly TransportInterface $transport,
        private readonly OAuthTokenProvider $tokenProvider,
        private readonly LoggerInterface $logger,
        private readonly ClockInterface $clock,
    ) {
    }

    public function verifySignature(VerifyWebhookSignatureRequest $request): VerifyWebhookSignatureResult
    {
        $this->config->ensureWebhookConfiguration();

        if ($this->config->maxWebhookTransmissionAgeSeconds !== null) {
            $request->assertTransmissionTimeWithin(
                now: $this->clock->now(),
                maxAgeSeconds: $this->config->maxWebhookTransmissionAgeSeconds,
                allowedFutureSkewSeconds: $this->config->allowedWebhookClockSkewSeconds,
            );
        }

        $token = $this->tokenProvider->token();
        $body = json_encode($request->toPayload($this->config->webhookId));

        if ($body === false) {
            throw new VerificationException('Unable to encode verify-webhook-signature request payload.');
        }

        $response = $this->transport->send(new HttpRequest(
            method: 'POST',
            url: $this->config->webhookVerificationUrl(),
            headers: [
                'Authorization' => 'Bearer '.$token,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
            body: $body,
            timeoutSeconds: $this->config->timeoutSeconds,
        ));

        $decoded = json_decode($response->body, true);
        if (!is_array($decoded)) {
            throw new VerificationException('PayPal webhook verification response is not valid JSON.');
        }

        if ($response->statusCode < 200 || $response->statusCode >= 300) {
            throw new VerificationException(
                sprintf('PayPal webhook verification request failed with HTTP %d.', $response->statusCode)
            );
        }

        $paypalStatus = (string) ($decoded['verification_status'] ?? 'FAILURE');
        $status = VerificationStatus::fromString($paypalStatus);

        $this->logger->info('PayPal verify-webhook-signature completed.', [
            'status' => $status->value,
            'http_status' => $response->statusCode,
        ]);

        return new VerifyWebhookSignatureResult(
            status: $status,
            paypalStatus: $paypalStatus,
            debugId: $response->header('paypal-debug-id'),
            httpStatusCode: $response->statusCode,
            rawResponse: $decoded,
        );
    }

    public function verifyWebhookSignature(VerifyWebhookSignatureRequest $request): VerifyWebhookSignatureResult
    {
        return $this->verifySignature($request);
    }

    /**
     * @param array<string,mixed> $headers
     */
    public function requestFromRawPayload(string $rawBody, array $headers, ?string $webhookId = null): VerifyWebhookSignatureRequest
    {
        return VerifyWebhookSignatureRequest::fromRawPayload(
            rawBody: $rawBody,
            headers: $headers,
            webhookId: $webhookId,
            strictPayPalCertUrlValidation: $this->config->strictPayPalCertUrlValidation,
        );
    }

    /**
     * @param array<string,mixed> $payload
     */
    public function parseEvent(array $payload): WebhookEventInterface
    {
        return EventFactory::fromPayload($payload);
    }

    public function parseRawEvent(string $rawBody): WebhookEventInterface
    {
        $decoded = json_decode($rawBody, true);
        if (!is_array($decoded)) {
            throw new VerificationException('Webhook event body is not valid JSON.');
        }

        return $this->parseEvent($decoded);
    }
}
