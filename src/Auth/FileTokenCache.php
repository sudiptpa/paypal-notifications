<?php

declare(strict_types=1);

namespace Sujip\PayPal\Notifications\Auth;

final class FileTokenCache implements TokenCacheInterface
{
    public function __construct(private readonly string $directory)
    {
    }

    public function get(string $key): ?OAuthToken
    {
        $path = $this->pathForKey($key);
        if (!is_file($path)) {
            return null;
        }

        $raw = @file_get_contents($path);
        if (!is_string($raw) || $raw === '') {
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
        $this->ensureDirectoryExists();

        $payload = json_encode([
            'access_token' => $token->accessToken,
            'expires_at' => $token->expiresAt->format(\DateTimeInterface::ATOM),
        ]);

        if (!is_string($payload)) {
            return;
        }

        @file_put_contents($this->pathForKey($key), $payload, LOCK_EX);
    }

    private function ensureDirectoryExists(): void
    {
        if (is_dir($this->directory)) {
            return;
        }

        @mkdir($this->directory, 0775, true);
    }

    private function pathForKey(string $key): string
    {
        return rtrim($this->directory, '/').'/token-'.hash('sha256', $key).'.json';
    }
}

