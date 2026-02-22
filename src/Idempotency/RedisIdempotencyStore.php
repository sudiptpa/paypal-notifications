<?php

declare(strict_types=1);

namespace Sujip\PayPal\Notifications\Idempotency;

use Sujip\PayPal\Notifications\Contracts\KeyValueStoreInterface;

final class RedisIdempotencyStore implements AtomicIdempotencyStoreInterface
{
    public function __construct(
        private readonly KeyValueStoreInterface $store,
        private readonly string $prefix = 'paypal_notifications:webhook_idempotency:',
        private readonly ?int $ttlSeconds = 604800,
    ) {
    }

    public function has(string $key): bool
    {
        return $this->store->get($this->cacheKey($key)) !== null;
    }

    public function put(string $key): void
    {
        $this->store->set($this->cacheKey($key), '1', $this->ttlSeconds);
    }

    public function putIfAbsent(string $key): bool
    {
        return $this->store->setIfAbsent($this->cacheKey($key), '1', $this->ttlSeconds);
    }

    private function cacheKey(string $key): string
    {
        return $this->prefix.hash('sha256', $key);
    }
}

