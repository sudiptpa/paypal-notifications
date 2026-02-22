<?php

declare(strict_types=1);

namespace Sujip\PayPal\Notifications\Contracts;

interface KeyValueStoreInterface
{
    public function get(string $key): ?string;

    public function set(string $key, string $value, ?int $ttlSeconds = null): void;

    public function setIfAbsent(string $key, string $value, ?int $ttlSeconds = null): bool;
}

