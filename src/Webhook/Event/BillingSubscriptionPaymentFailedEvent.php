<?php

declare(strict_types=1);

namespace Sujip\PayPal\Notifications\Webhook\Event;

final readonly class BillingSubscriptionPaymentFailedEvent extends AbstractWebhookEvent
{
    public function subscriptionId(): ?string
    {
        return $this->resourceString('id');
    }

    public function subscriptionStatus(): ?string
    {
        return $this->resourceString('status');
    }

    public function failedPaymentAmountValue(): ?string
    {
        $amount = $this->resourceArray('billing_info');
        $failed = $amount['last_failed_payment'] ?? null;
        if (!is_array($failed)) {
            return null;
        }

        $failedAmount = $failed['amount'] ?? null;
        if (!is_array($failedAmount)) {
            return null;
        }

        $value = $failedAmount['value'] ?? null;

        return is_string($value) && $value !== '' ? $value : null;
    }

    public function failedPaymentCurrencyCode(): ?string
    {
        $amount = $this->resourceArray('billing_info');
        $failed = $amount['last_failed_payment'] ?? null;
        if (!is_array($failed)) {
            return null;
        }

        $failedAmount = $failed['amount'] ?? null;
        if (!is_array($failedAmount)) {
            return null;
        }

        $code = $failedAmount['currency_code'] ?? null;

        return is_string($code) && $code !== '' ? $code : null;
    }
}
