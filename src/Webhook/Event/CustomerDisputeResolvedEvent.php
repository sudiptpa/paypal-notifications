<?php

declare(strict_types=1);

namespace Sujip\PayPal\Notifications\Webhook\Event;

final readonly class CustomerDisputeResolvedEvent extends AbstractWebhookEvent
{
    public function disputeId(): ?string
    {
        return $this->resourceString('dispute_id') ?? $this->resourceString('id');
    }

    public function disputeStatus(): ?string
    {
        return $this->resourceString('status');
    }

    public function disputeOutcome(): ?string
    {
        return $this->resourceString('dispute_outcome') ?? $this->resourceString('outcome');
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
