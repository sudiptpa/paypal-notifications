<?php

declare(strict_types=1);

namespace Sujip\PayPal\Notifications\Resource;

use Sujip\PayPal\Notifications\Auth\OAuthTokenProvider;
use Sujip\PayPal\Notifications\Config\ClientConfig;
use Sujip\PayPal\Notifications\Contracts\ClockInterface;
use Sujip\PayPal\Notifications\Contracts\LoggerInterface;
use Sujip\PayPal\Notifications\Contracts\TransportInterface;
use Sujip\PayPal\Notifications\Contracts\WebhookRequestAdapterInterface;
use Sujip\PayPal\Notifications\Enum\VerificationStatus;
use Sujip\PayPal\Notifications\Exception\MalformedPayload;
use Sujip\PayPal\Notifications\Exception\SignatureVerificationFailed;
use Sujip\PayPal\Notifications\Exception\TransportException;
use Sujip\PayPal\Notifications\Exception\TransportFailed;
use Sujip\PayPal\Notifications\Exception\VerificationException;
use Sujip\PayPal\Notifications\Http\HttpRequest;
use Sujip\PayPal\Notifications\Webhook\WebhookCertUrlPolicy;
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
        private readonly WebhookCertUrlPolicy $certUrlPolicy = new WebhookCertUrlPolicy(),
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

        if ($this->config->strictPayPalCertUrlValidation) {
            $this->certUrlPolicy->assertValid($request->certUrl, $this->config);
        }

        $token = $this->tokenProvider->token();
        $body = json_encode($request->toPayload($this->config->webhookId));

        if ($body === false) {
            throw new MalformedPayload('Unable to encode verify-webhook-signature request payload.');
        }

        $attempt = 0;
        $maxRetries = $this->config->verificationMaxRetries;
        $retryableStatusCodes = array_flip($this->config->verificationRetryHttpStatusCodes);
        $response = null;
        $decoded = null;

        while (true) {
            try {
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
            } catch (TransportException $exception) {
                if ($attempt < $maxRetries) {
                    $this->sleepForRetry($attempt);
                    ++$attempt;
                    continue;
                }

                throw new TransportFailed('PayPal webhook verification request failed during transport.', 0, $exception);
            }

            if ($response->statusCode >= 200 && $response->statusCode < 300) {
                $decoded = json_decode($response->body, true);
                if (!is_array($decoded)) {
                    throw new MalformedPayload('PayPal webhook verification response is not valid JSON.');
                }
                break;
            }

            if ($attempt < $maxRetries && isset($retryableStatusCodes[$response->statusCode])) {
                $this->sleepForRetry($attempt);
                ++$attempt;
                continue;
            }

            throw new SignatureVerificationFailed(
                sprintf('PayPal webhook verification request failed with HTTP %d.', $response->statusCode)
            );
        }

        if ($decoded === null) {
            throw new VerificationException('PayPal webhook verification did not produce a valid response payload.');
        }

        /** @var array<string, mixed> $decoded */
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

    public function requestFromAdapter(WebhookRequestAdapterInterface $adapter): VerifyWebhookSignatureRequest
    {
        return $this->requestFromRawPayload(
            rawBody: $adapter->rawBody(),
            headers: $adapter->headers(),
            webhookId: $adapter->webhookId(),
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
            throw new MalformedPayload('Webhook event body is not valid JSON.');
        }

        return $this->parseEvent($decoded);
    }

    private function sleepForRetry(int $attempt): void
    {
        $baseBackoffMs = $this->config->verificationRetryBackoffMs;
        $maxBackoffMs = max($baseBackoffMs, $this->config->verificationRetryMaxBackoffMs);
        $sleepMs = min($maxBackoffMs, $baseBackoffMs * (2 ** $attempt));

        if ($sleepMs <= 0) {
            return;
        }

        usleep($sleepMs * 1000);
    }
}
