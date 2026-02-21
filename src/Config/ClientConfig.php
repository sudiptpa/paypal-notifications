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
        public ?int $maxWebhookTransmissionAgeSeconds = 300,
        public int $allowedWebhookClockSkewSeconds = 30,
        public bool $strictPayPalCertUrlValidation = true,
        /** @var list<string> */
        public array $trustedWebhookCertHostSuffixes = ['paypal.com'],
        public string $requiredWebhookCertPathPrefix = '/v1/notifications/certs/',
        public bool $requireDefaultHttpsPortForWebhookCertUrl = true,
    ) {
        if ($this->timeoutSeconds <= 0) {
            throw new ConfigurationException('timeoutSeconds must be greater than zero.');
        }

        if ($this->maxWebhookTransmissionAgeSeconds !== null && $this->maxWebhookTransmissionAgeSeconds < 0) {
            throw new ConfigurationException('maxWebhookTransmissionAgeSeconds must be null or >= 0.');
        }

        if ($this->allowedWebhookClockSkewSeconds < 0) {
            throw new ConfigurationException('allowedWebhookClockSkewSeconds must be >= 0.');
        }

        if ($this->requiredWebhookCertPathPrefix === '') {
            throw new ConfigurationException('requiredWebhookCertPathPrefix must not be empty.');
        }

        if ($this->trustedWebhookCertHostSuffixes === []) {
            throw new ConfigurationException('trustedWebhookCertHostSuffixes must not be empty.');
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
