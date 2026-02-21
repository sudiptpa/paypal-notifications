<?php

declare(strict_types=1);

namespace Sujip\PayPal\Notifications\Webhook\Event;

interface WebhookEventInterface
{
    public function id(): string;

    public function eventType(): string;

    public function createTime(): ?string;

    public function resourceType(): ?string;

    public function summary(): ?string;

    /** @return array<string,mixed> */
    public function resource(): array;

    /** @return array<string,mixed> */
    public function raw(): array;
}
