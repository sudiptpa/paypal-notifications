<?php

declare(strict_types=1);

namespace Sujip\PayPal\Notifications\Webhook\Event;

final readonly class CheckoutOrderApprovedEvent extends AbstractWebhookEvent
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

    public function payerEmail(): ?string
    {
        $payer = $this->resourceArray('payer');
        $email = $payer['email_address'] ?? null;

        return is_string($email) && $email !== '' ? $email : null;
    }

    public function purchaseUnitReferenceId(): ?string
    {
        $purchaseUnits = $this->resource()['purchase_units'] ?? null;
        if (!is_array($purchaseUnits) || $purchaseUnits === []) {
            return null;
        }

        $first = reset($purchaseUnits);
        if (!is_array($first)) {
            return null;
        }

        $referenceId = $first['reference_id'] ?? null;

        return is_string($referenceId) && $referenceId !== '' ? $referenceId : null;
    }
}
