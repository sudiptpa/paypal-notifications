<?php

declare(strict_types=1);

namespace Sujip\PayPal\Notifications\Webhook\Event;

final readonly class PaymentPayoutsItemSucceededEvent extends AbstractWebhookEvent
{
    public function payoutItemId(): ?string
    {
        $item = $this->resourceArray('payout_item');
        $id = $item['payout_item_id'] ?? null;

        return is_string($id) && $id !== '' ? $id : null;
    }

    public function payoutBatchId(): ?string
    {
        $item = $this->resourceArray('payout_item');
        $batchId = $item['payout_batch_id'] ?? null;

        return is_string($batchId) && $batchId !== '' ? $batchId : null;
    }

    public function transactionStatus(): ?string
    {
        $item = $this->resourceArray('payout_item');
        $status = $item['transaction_status'] ?? null;

        return is_string($status) && $status !== '' ? $status : null;
    }
}
