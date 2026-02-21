<?php

declare(strict_types=1);

use Sujip\PayPal\Notifications\Contracts\WebhookRequestAdapterInterface;

/**
 * Template for framework integration.
 * Replace `mixed` with your framework request type and map methods accordingly.
 */
final readonly class FrameworkWebhookAdapter implements WebhookRequestAdapterInterface
{
    public function __construct(private mixed $request, private ?string $webhookId = null)
    {
    }

    public function rawBody(): string
    {
        // Example: return (string) $this->request->getContent();
        return '';
    }

    public function headers(): array
    {
        // Example: return $this->request->headers->all();
        return [];
    }

    public function webhookId(): ?string
    {
        return $this->webhookId;
    }
}
