<?php

declare(strict_types=1);

namespace Sujip\PayPal\Notifications\Idempotency;

interface IdempotencyStoreInterface
{
    public function has(string $key): bool;

    public function put(string $key): void;
}
