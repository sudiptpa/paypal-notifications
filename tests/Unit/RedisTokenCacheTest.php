<?php

declare(strict_types=1);

namespace Sujip\PayPal\Notifications\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Sujip\PayPal\Notifications\Auth\OAuthToken;
use Sujip\PayPal\Notifications\Auth\RedisTokenCache;
use Sujip\PayPal\Notifications\Tests\Fakes\InMemoryKeyValueStore;

final class RedisTokenCacheTest extends TestCase
{
    public function testStoresAndLoadsToken(): void
    {
        $store = new InMemoryKeyValueStore();
        $cache = new RedisTokenCache($store);

        $cache->put('scope-key', new OAuthToken('token-value', new \DateTimeImmutable('+5 minutes')));

        $token = $cache->get('scope-key');

        $this->assertNotNull($token);
        $this->assertSame('token-value', $token->accessToken);
    }
}

