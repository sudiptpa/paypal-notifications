<?php

declare(strict_types=1);

namespace Sujip\PayPal\Notifications\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Sujip\PayPal\Notifications\Idempotency\RedisIdempotencyStore;
use Sujip\PayPal\Notifications\Tests\Fakes\InMemoryKeyValueStore;

final class RedisIdempotencyStoreTest extends TestCase
{
    public function testPutIfAbsentPreventsDuplicates(): void
    {
        $store = new RedisIdempotencyStore(new InMemoryKeyValueStore());

        $this->assertTrue($store->putIfAbsent('WH-1'));
        $this->assertFalse($store->putIfAbsent('WH-1'));
        $this->assertTrue($store->has('WH-1'));
    }
}

