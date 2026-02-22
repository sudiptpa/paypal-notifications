<?php

declare(strict_types=1);

namespace Sujip\PayPal\Notifications\Webhook\Event\Disputes;

use Sujip\PayPal\Notifications\Webhook\Event\AbstractWebhookEvent;

readonly class CustomerDisputeCreatedEvent extends AbstractWebhookEvent
{
    public function disputeId(): ?string
    {
        return $this->resourceString('dispute_id') ?? $this->resourceString('id');
    }

    public function disputeStatus(): ?string
    {
        return $this->resourceString('status');
    }

    public function disputeReason(): ?string
    {
        return $this->resourceString('reason');
    }

    public function amountValue(): ?string
    {
        $amount = $this->resourceArray('dispute_amount');
        $value = $amount['value'] ?? null;

        return is_string($value) && $value !== '' ? $value : null;
    }

    public function amountCurrencyCode(): ?string
    {
        $amount = $this->resourceArray('dispute_amount');
        $code = $amount['currency_code'] ?? null;

        return is_string($code) && $code !== '' ? $code : null;
    }
}
