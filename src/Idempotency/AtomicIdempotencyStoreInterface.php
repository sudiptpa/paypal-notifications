<?php

declare(strict_types=1);

namespace Sujip\PayPal\Notifications\Idempotency;

interface AtomicIdempotencyStoreInterface extends IdempotencyStoreInterface
{
    public function putIfAbsent(string $key): bool;
}

