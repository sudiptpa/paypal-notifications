<?php

declare(strict_types=1);

namespace Sujip\PayPal\Notifications\Auth;

interface TokenCacheInterface
{
    public function get(string $key): ?OAuthToken;

    public function put(string $key, OAuthToken $token): void;
}

