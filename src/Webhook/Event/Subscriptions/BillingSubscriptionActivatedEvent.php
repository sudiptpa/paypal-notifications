<?php

declare(strict_types=1);

namespace Sujip\PayPal\Notifications\Webhook\Event\Subscriptions;

use Sujip\PayPal\Notifications\Webhook\Event\AbstractWebhookEvent;

readonly class BillingSubscriptionActivatedEvent extends AbstractWebhookEvent
{
    public function subscriptionId(): ?string
    {
        return $this->resourceString('id');
    }

    public function subscriptionStatus(): ?string
    {
        return $this->resourceString('status');
    }

    public function planId(): ?string
    {
        return $this->resourceString('plan_id');
    }
}
