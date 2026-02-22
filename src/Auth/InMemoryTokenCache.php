<?php

declare(strict_types=1);

namespace Sujip\PayPal\Notifications\Auth;

final class InMemoryTokenCache implements TokenCacheInterface
{
    /** @var array<string, OAuthToken> */
    private array $tokens = [];

    public function get(string $key): ?OAuthToken
    {
        return $this->tokens[$key] ?? null;
    }

    public function put(string $key, OAuthToken $token): void
    {
        $this->tokens[$key] = $token;
    }
}

