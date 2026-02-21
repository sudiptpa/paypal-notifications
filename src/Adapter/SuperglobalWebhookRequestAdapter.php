<?php

declare(strict_types=1);

namespace Sujip\PayPal\Notifications\Adapter;

use Sujip\PayPal\Notifications\Contracts\WebhookRequestAdapterInterface;

final readonly class SuperglobalWebhookRequestAdapter implements WebhookRequestAdapterInterface
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

    public static function fromGlobals(?string $webhookId = null): self
    {
        $rawBody = file_get_contents('php://input') ?: '';
        $headers = function_exists('getallheaders') ? getallheaders() : $_SERVER;

        return new self($rawBody, $headers, $webhookId);
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
