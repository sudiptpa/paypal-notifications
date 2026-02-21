<?php

declare(strict_types=1);

namespace Sujip\PayPal\Notifications\Config;

use Sujip\PayPal\Notifications\Exception\ConfigurationException;

final readonly class ClientConfig
{
    public function __construct(
        public string $clientId,
        #[\SensitiveParameter]
        public string $clientSecret,
        public string $webhookId,
        public Environment $environment = Environment::Sandbox,
        public int $timeoutSeconds = 10,
    ) {
        if ($this->timeoutSeconds <= 0) {
            throw new ConfigurationException('timeoutSeconds must be greater than zero.');
        }
    }

    public function oauthTokenUrl(): string
    {
        return $this->environment->apiBaseUrl().'/v1/oauth2/token';
    }

    public function webhookVerificationUrl(): string
    {
        return $this->environment->apiBaseUrl().'/v1/notifications/verify-webhook-signature';
    }

    public function ensureWebhookConfiguration(): void
    {
        if ($this->clientId === '' || $this->clientSecret === '' || $this->webhookId === '') {
            throw new ConfigurationException(
                'clientId, clientSecret and webhookId are required for webhook signature verification.'
            );
        }
    }
}
