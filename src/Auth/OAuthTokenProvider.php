<?php

declare(strict_types=1);

namespace Sujip\PayPal\Notifications\Auth;

use Sujip\PayPal\Notifications\Config\ClientConfig;
use Sujip\PayPal\Notifications\Contracts\ClockInterface;
use Sujip\PayPal\Notifications\Contracts\LoggerInterface;
use Sujip\PayPal\Notifications\Contracts\TransportInterface;
use Sujip\PayPal\Notifications\Exception\AuthenticationException;
use Sujip\PayPal\Notifications\Exception\TransportException;
use Sujip\PayPal\Notifications\Http\HttpRequest;

final class OAuthTokenProvider
{
    private ?OAuthToken $cachedToken = null;
    private readonly string $cacheKey;

    public function __construct(
        private readonly ClientConfig $config,
        private readonly TransportInterface $transport,
        private readonly ClockInterface $clock,
        private readonly LoggerInterface $logger,
        private readonly ?TokenCacheInterface $tokenCache = null,
    ) {
        $this->cacheKey = hash('sha256', $this->config->environment->name.'|'.$this->config->clientId);
    }

    public function token(): string
    {
        $this->config->ensureWebhookConfiguration();

        $now = $this->clock->now();
        if ($this->cachedToken !== null && !$this->cachedToken->isExpiredAt($now)) {
            return $this->cachedToken->accessToken;
        }

        if ($this->cachedToken === null && $this->tokenCache !== null) {
            $persisted = $this->tokenCache->get($this->cacheKey);
            if ($persisted !== null && !$persisted->isExpiredAt($now)) {
                $this->cachedToken = $persisted;
                return $persisted->accessToken;
            }
        }

        $request = new HttpRequest(
            method: 'POST',
            url: $this->config->oauthTokenUrl(),
            headers: [
                'Authorization' => 'Basic '.base64_encode($this->config->clientId.':'.$this->config->clientSecret),
                'Content-Type' => 'application/x-www-form-urlencoded',
                'Accept' => 'application/json',
            ],
            body: 'grant_type=client_credentials',
            timeoutSeconds: $this->config->timeoutSeconds,
        );

        try {
            $response = $this->transport->send($request);
        } catch (TransportException $exception) {
            $this->logger->error('PayPal OAuth token request transport error.');
            throw new AuthenticationException('Unable to retrieve PayPal OAuth token.', 0, $exception);
        }

        if ($response->statusCode < 200 || $response->statusCode >= 300) {
            $this->logger->error('PayPal OAuth token request failed.', ['status' => $response->statusCode]);
            throw new AuthenticationException('Unable to retrieve PayPal OAuth token.');
        }

        $decoded = json_decode($response->body, true);
        if (!is_array($decoded)) {
            throw new AuthenticationException('PayPal OAuth response is not valid JSON.');
        }

        $token = (string) ($decoded['access_token'] ?? '');
        $expiresIn = (int) ($decoded['expires_in'] ?? 0);

        if ($token === '' || $expiresIn <= 0) {
            throw new AuthenticationException('PayPal OAuth response did not contain a usable access token.');
        }

        // Keep a small safety window to avoid edge-expiry during outbound verify calls.
        $safetyWindow = min(30, max(5, intdiv($expiresIn, 20)));

        $this->cachedToken = new OAuthToken(
            accessToken: $token,
            expiresAt: $now->modify(sprintf('+%d seconds', max(1, $expiresIn - $safetyWindow))),
        );
        $this->tokenCache?->put($this->cacheKey, $this->cachedToken);

        return $token;
    }
}
