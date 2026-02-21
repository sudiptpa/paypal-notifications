<?php

declare(strict_types=1);

namespace Sujip\PayPal\Notifications\InstantPaymentNotification;

final readonly class VerifyInstantPaymentNotificationRequest
{
    /**
     * @param array<string,string> $payload
     */
    public function __construct(
        public array $payload,
        public ?string $rawBody = null,
    ) {
    }

    /** @param array<string,mixed> $data */
    public static function fromArray(array $data): self
    {
        $payload = [];

        foreach ($data as $key => $value) {
            if (is_scalar($value) || $value === null) {
                $payload[(string) $key] = (string) $value;
            }
        }

        return new self($payload, null);
    }

    public static function fromRawBody(string $rawBody): self
    {
        $parsed = [];
        parse_str($rawBody, $parsed);

        $normalized = [];
        foreach ($parsed as $key => $value) {
            $normalized[(string) $key] = $value;
        }

        return self::fromArray($normalized)->withRawBody($rawBody);
    }

    public function withRawBody(string $rawBody): self
    {
        return new self($this->payload, $rawBody);
    }

    public function toUrlEncodedBody(): string
    {
        return http_build_query($this->payload, '', '&', PHP_QUERY_RFC3986);
    }
}
