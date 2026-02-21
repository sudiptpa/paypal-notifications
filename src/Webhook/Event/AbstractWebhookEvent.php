<?php

declare(strict_types=1);

namespace Sujip\PayPal\Notifications\Webhook\Event;

abstract readonly class AbstractWebhookEvent implements WebhookEventInterface
{
    /**
     * @param array<string,mixed> $payload
     */
    public function __construct(protected array $payload)
    {
    }

    public function id(): string
    {
        return (string) ($this->payload['id'] ?? '');
    }

    public function eventType(): string
    {
        return (string) ($this->payload['event_type'] ?? 'UNKNOWN');
    }

    public function is(string $eventType): bool
    {
        return $this->eventType() === $eventType;
    }

    public function createTime(): ?string
    {
        $value = $this->payload['create_time'] ?? null;

        return is_string($value) && $value !== '' ? $value : null;
    }

    public function resourceType(): ?string
    {
        $value = $this->payload['resource_type'] ?? null;

        return is_string($value) && $value !== '' ? $value : null;
    }

    public function summary(): ?string
    {
        $value = $this->payload['summary'] ?? null;

        return is_string($value) && $value !== '' ? $value : null;
    }

    public function resource(): array
    {
        $resource = $this->payload['resource'] ?? [];

        return is_array($resource) ? $resource : [];
    }

    public function raw(): array
    {
        return $this->payload;
    }

    protected function resourceString(string $key): ?string
    {
        $resource = $this->resource();
        $value = $resource[$key] ?? null;

        return is_string($value) && $value !== '' ? $value : null;
    }

    /**
     * @return array<string,mixed>
     */
    protected function resourceArray(string $key): array
    {
        $resource = $this->resource();
        $value = $resource[$key] ?? [];

        return is_array($value) ? $value : [];
    }
}
