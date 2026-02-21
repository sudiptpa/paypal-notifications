<?php

declare(strict_types=1);

namespace Sujip\PayPal\Notifications\Webhook;

use Sujip\PayPal\Notifications\Exception\InvalidWebhookHeadersException;

final readonly class WebhookHeaders
{
    /**
     * @param array<string,string> $normalized
     */
    public function __construct(
        private array $normalized,
    ) {
    }

    /**
     * @param array<string,mixed> $headers
     */
    public static function fromArray(array $headers): self
    {
        $normalized = [];

        foreach ($headers as $name => $value) {
            $key = strtolower(str_replace('_', '-', (string) $name));
            if (str_starts_with($key, 'http-')) {
                $key = substr($key, 5);
            }

            $normalized[$key] = is_array($value)
                ? (string) (reset($value) ?: '')
                : (string) $value;
        }

        return new self($normalized);
    }

    public function transmissionId(): string
    {
        return $this->required('paypal-transmission-id');
    }

    public function transmissionTime(): string
    {
        return $this->required('paypal-transmission-time');
    }

    public function transmissionSig(): string
    {
        return $this->required('paypal-transmission-sig');
    }

    public function certUrl(): string
    {
        return $this->required('paypal-cert-url');
    }

    public function authAlgo(): string
    {
        return $this->required('paypal-auth-algo');
    }

    private function required(string $name): string
    {
        $value = $this->normalized[$name] ?? '';

        if ($value === '') {
            throw new InvalidWebhookHeadersException(
                sprintf('Missing required PayPal header: %s', strtoupper($name))
            );
        }

        return $value;
    }
}
