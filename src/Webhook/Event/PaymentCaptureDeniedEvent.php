<?php

declare(strict_types=1);

namespace Sujip\PayPal\Notifications\Webhook\Event;

final readonly class PaymentCaptureDeniedEvent extends AbstractWebhookEvent
{
    public function captureId(): ?string
    {
        return $this->resourceString('id');
    }

    public function captureStatus(): ?string
    {
        return $this->resourceString('status');
    }

    public function amountValue(): ?string
    {
        $amount = $this->resourceArray('amount');
        $value = $amount['value'] ?? null;

        return is_string($value) && $value !== '' ? $value : null;
    }

    public function amountCurrencyCode(): ?string
    {
        $amount = $this->resourceArray('amount');
        $code = $amount['currency_code'] ?? null;

        return is_string($code) && $code !== '' ? $code : null;
    }

    public function statusReason(): ?string
    {
        return $this->resourceString('status_details');
    }
}
