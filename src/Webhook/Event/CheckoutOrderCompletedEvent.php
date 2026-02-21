<?php

declare(strict_types=1);

namespace Sujip\PayPal\Notifications\Webhook\Event;

final readonly class CheckoutOrderCompletedEvent extends AbstractWebhookEvent
{
    public function orderId(): ?string
    {
        return $this->resourceString('id');
    }

    public function orderStatus(): ?string
    {
        return $this->resourceString('status');
    }

    public function payerId(): ?string
    {
        $payer = $this->resourceArray('payer');
        $payerId = $payer['payer_id'] ?? null;

        return is_string($payerId) && $payerId !== '' ? $payerId : null;
    }

    public function amountValue(): ?string
    {
        $purchaseUnits = $this->resource()['purchase_units'] ?? null;
        if (!is_array($purchaseUnits) || $purchaseUnits === []) {
            return null;
        }

        $first = reset($purchaseUnits);
        if (!is_array($first)) {
            return null;
        }

        $amount = $first['amount'] ?? null;
        if (!is_array($amount)) {
            return null;
        }

        $value = $amount['value'] ?? null;

        return is_string($value) && $value !== '' ? $value : null;
    }

    public function amountCurrencyCode(): ?string
    {
        $purchaseUnits = $this->resource()['purchase_units'] ?? null;
        if (!is_array($purchaseUnits) || $purchaseUnits === []) {
            return null;
        }

        $first = reset($purchaseUnits);
        if (!is_array($first)) {
            return null;
        }

        $amount = $first['amount'] ?? null;
        if (!is_array($amount)) {
            return null;
        }

        $code = $amount['currency_code'] ?? null;

        return is_string($code) && $code !== '' ? $code : null;
    }
}
