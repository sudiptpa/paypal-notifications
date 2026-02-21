<?php

declare(strict_types=1);

namespace Sujip\PayPal\Notifications\Contracts;

interface WebhookRequestAdapterInterface
{
    public function rawBody(): string;

    /** @return array<string,mixed> */
    public function headers(): array;

    public function webhookId(): ?string;
}
