<?php

declare(strict_types=1);

namespace Sujip\PayPal\Notifications\Tests\Fakes;

use Sujip\PayPal\Notifications\Contracts\KeyValueStoreInterface;

final class InMemoryKeyValueStore implements KeyValueStoreInterface
{
    /** @var array<string, string> */
    private array $values = [];

    public function get(string $key): ?string
    {
        return $this->values[$key] ?? null;
    }

    public function set(string $key, string $value, ?int $ttlSeconds = null): void
    {
        $this->values[$key] = $value;
    }

    public function setIfAbsent(string $key, string $value, ?int $ttlSeconds = null): bool
    {
        if (array_key_exists($key, $this->values)) {
            return false;
        }

        $this->values[$key] = $value;
        return true;
    }
}

