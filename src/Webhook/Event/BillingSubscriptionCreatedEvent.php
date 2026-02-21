<?php

declare(strict_types=1);

namespace Sujip\PayPal\Notifications\Webhook\Event;

final readonly class BillingSubscriptionCreatedEvent extends AbstractWebhookEvent
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

    public function payerId(): ?string
    {
        $subscriber = $this->resourceArray('subscriber');
        $payerId = $subscriber['payer_id'] ?? null;

        return is_string($payerId) && $payerId !== '' ? $payerId : null;
    }
}
