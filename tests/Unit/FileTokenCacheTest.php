<?php

declare(strict_types=1);

namespace Sujip\PayPal\Notifications\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Sujip\PayPal\Notifications\Auth\FileTokenCache;
use Sujip\PayPal\Notifications\Auth\OAuthToken;

final class FileTokenCacheTest extends TestCase
{
    public function testStoresAndLoadsToken(): void
    {
        $directory = sys_get_temp_dir().'/paypal-notifications-token-cache-'.uniqid('', true);
        $cache = new FileTokenCache($directory);

        $expiresAt = new \DateTimeImmutable('+10 minutes');
        $cache->put('scope-key', new OAuthToken('token-value', $expiresAt));

        $token = $cache->get('scope-key');

        $this->assertNotNull($token);
        $this->assertSame('token-value', $token->accessToken);
    }

    public function testReturnsNullForExpiredToken(): void
    {
        $directory = sys_get_temp_dir().'/paypal-notifications-token-cache-'.uniqid('', true);
        $cache = new FileTokenCache($directory);

        $expiresAt = new \DateTimeImmutable('-10 minutes');
        $cache->put('scope-key', new OAuthToken('token-value', $expiresAt));

        $this->assertNull($cache->get('scope-key'));
    }
}

