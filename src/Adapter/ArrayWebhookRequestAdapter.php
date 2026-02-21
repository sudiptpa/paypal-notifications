<?php

declare(strict_types=1);

namespace Sujip\PayPal\Notifications\Adapter;

use Sujip\PayPal\Notifications\Contracts\WebhookRequestAdapterInterface;

final readonly class ArrayWebhookRequestAdapter implements WebhookRequestAdapterInterface
{
    /**
     * @param array<string,mixed> $headers
     */
    public function __construct(
        private string $rawBody,
        private array $headers,
        private ?string $webhookId = null,
    ) {
    }

    public function rawBody(): string
    {
        return $this->rawBody;
    }

    public function headers(): array
    {
        return $this->headers;
    }

    public function webhookId(): ?string
    {
        return $this->webhookId;
    }
}
