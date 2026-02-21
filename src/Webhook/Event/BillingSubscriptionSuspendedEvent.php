<?php

declare(strict_types=1);

namespace Sujip\PayPal\Notifications\Webhook\Event;

final readonly class BillingSubscriptionSuspendedEvent extends AbstractWebhookEvent
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

    public function statusChangeNote(): ?string
    {
        return $this->resourceString('status_change_note');
    }
}
