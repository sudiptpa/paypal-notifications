<?php

declare(strict_types=1);

namespace Sujip\PayPal\Notifications\Idempotency;

final class InMemoryIdempotencyStore implements AtomicIdempotencyStoreInterface
{
    /** @var array<string, true> */
    private array $keys = [];

    public function has(string $key): bool
    {
        return isset($this->keys[$key]);
    }

    public function put(string $key): void
    {
        $this->keys[$key] = true;
    }

    public function putIfAbsent(string $key): bool
    {
        if ($this->has($key)) {
            return false;
        }

        $this->put($key);

        return true;
    }
}
