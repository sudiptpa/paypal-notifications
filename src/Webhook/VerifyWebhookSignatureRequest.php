<?php

declare(strict_types=1);

namespace Sujip\PayPal\Notifications\Webhook;

use Sujip\PayPal\Notifications\Exception\InvalidPayloadException;

final readonly class VerifyWebhookSignatureRequest
{
    /**
     * @param array<string,mixed> $webhookEvent
     */
    public function __construct(
        public string $transmissionId,
        public string $transmissionTime,
        public string $transmissionSig,
        public string $certUrl,
        public string $authAlgo,
        public array $webhookEvent,
        public string $rawBody,
        public ?string $webhookId = null,
    ) {
        if (
            $this->transmissionId === ''
            || $this->transmissionTime === ''
            || $this->transmissionSig === ''
            || $this->certUrl === ''
            || $this->authAlgo === ''
        ) {
            throw new InvalidPayloadException('Missing required fields for webhook signature verification request.');
        }

        $this->assertPayPalCertUrl($this->certUrl);
    }

    /**
     * @param array<string,mixed> $headers
     */
    public static function fromRawPayload(string $rawBody, array $headers, ?string $webhookId = null): self
    {
        $decoded = json_decode($rawBody, true);
        if (!is_array($decoded)) {
            throw new InvalidPayloadException('Webhook payload body is not valid JSON.');
        }

        $paypalHeaders = WebhookHeaders::fromArray($headers);

        return new self(
            transmissionId: $paypalHeaders->transmissionId(),
            transmissionTime: $paypalHeaders->transmissionTime(),
            transmissionSig: $paypalHeaders->transmissionSig(),
            certUrl: $paypalHeaders->certUrl(),
            authAlgo: $paypalHeaders->authAlgo(),
            webhookEvent: $decoded,
            rawBody: $rawBody,
            webhookId: $webhookId,
        );
    }

    /**
     * @return array<string,mixed>
     */
    public function toPayload(string $fallbackWebhookId): array
    {
        return [
            'auth_algo' => $this->authAlgo,
            'cert_url' => $this->certUrl,
            'transmission_id' => $this->transmissionId,
            'transmission_sig' => $this->transmissionSig,
            'transmission_time' => $this->transmissionTime,
            'webhook_id' => $this->webhookId ?: $fallbackWebhookId,
            'webhook_event' => $this->webhookEvent,
        ];
    }

    private function assertPayPalCertUrl(string $certUrl): void
    {
        $parts = parse_url($certUrl);
        if (!is_array($parts)) {
            throw new InvalidPayloadException('cert_url is not a valid URL.');
        }

        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = strtolower((string) ($parts['host'] ?? ''));

        if ($scheme !== 'https') {
            throw new InvalidPayloadException('cert_url must use https.');
        }

        $isTrustedHost = $host === 'paypal.com'
            || str_ends_with($host, '.paypal.com');

        if (!$isTrustedHost) {
            throw new InvalidPayloadException('cert_url must be hosted on a PayPal domain.');
        }
    }
}
