<?php

declare(strict_types=1);

namespace Sujip\PayPal\Notifications\Auth;

use Sujip\PayPal\Notifications\Contracts\KeyValueStoreInterface;

final class RedisTokenCache implements TokenCacheInterface
{
    public function __construct(
        private readonly KeyValueStoreInterface $store,
        private readonly string $prefix = 'paypal_notifications:oauth_token:',
    ) {
    }

    public function get(string $key): ?OAuthToken
    {
        $raw = $this->store->get($this->cacheKey($key));
        if ($raw === null || $raw === '') {
            return null;
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return null;
        }

        $token = trim((string) ($decoded['access_token'] ?? ''));
        $expiresAtRaw = (string) ($decoded['expires_at'] ?? '');
        if ($token === '' || $expiresAtRaw === '') {
            return null;
        }

        try {
            $expiresAt = new \DateTimeImmutable($expiresAtRaw);
        } catch (\Exception) {
            return null;
        }

        if ($expiresAt <= new \DateTimeImmutable('now')) {
            return null;
        }

        return new OAuthToken($token, $expiresAt);
    }

    public function put(string $key, OAuthToken $token): void
    {
        $payload = json_encode([
            'access_token' => $token->accessToken,
            'expires_at' => $token->expiresAt->format(\DateTimeInterface::ATOM),
        ]);
        if (!is_string($payload)) {
            return;
        }

        $ttl = max(1, $token->expiresAt->getTimestamp() - time());
        $this->store->set($this->cacheKey($key), $payload, $ttl);
    }

    private function cacheKey(string $key): string
    {
        return $this->prefix.hash('sha256', $key);
    }
}

