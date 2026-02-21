<?php

declare(strict_types=1);

namespace Sujip\PayPal\Notifications;

use Sujip\PayPal\Notifications\Auth\OAuthTokenProvider;
use Sujip\PayPal\Notifications\Config\ClientConfig;
use Sujip\PayPal\Notifications\Config\SystemClock;
use Sujip\PayPal\Notifications\Contracts\ClockInterface;
use Sujip\PayPal\Notifications\Contracts\LoggerInterface;
use Sujip\PayPal\Notifications\Contracts\TransportInterface;
use Sujip\PayPal\Notifications\Log\NullLogger;
use Sujip\PayPal\Notifications\Resource\InstantPaymentNotificationResource;
use Sujip\PayPal\Notifications\Resource\WebhooksResource;

final class PayPalClient
{
    private readonly WebhooksResource $webhooks;
    private readonly InstantPaymentNotificationResource $instantPaymentNotification;

    public function __construct(
        private readonly ClientConfig $config,
        private readonly TransportInterface $transport,
        ?LoggerInterface $logger = null,
        ?ClockInterface $clock = null,
    ) {
        $logger ??= new NullLogger();
        $clock ??= new SystemClock();

        $tokenProvider = new OAuthTokenProvider($this->config, $this->transport, $clock, $logger);

        $this->webhooks = new WebhooksResource($this->config, $this->transport, $tokenProvider, $logger);
        $this->instantPaymentNotification = new InstantPaymentNotificationResource(
            $this->config,
            $this->transport,
            $logger,
        );
    }

    public function webhooks(): WebhooksResource
    {
        return $this->webhooks;
    }

    public function instantPaymentNotification(): InstantPaymentNotificationResource
    {
        return $this->instantPaymentNotification;
    }

    public function ipn(): InstantPaymentNotificationResource
    {
        return $this->instantPaymentNotification();
    }
}
