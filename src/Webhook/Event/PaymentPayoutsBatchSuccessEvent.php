<?php

declare(strict_types=1);

namespace Sujip\PayPal\Notifications\Webhook\Event;

final readonly class PaymentPayoutsBatchSuccessEvent extends AbstractWebhookEvent
{
    public function payoutBatchId(): ?string
    {
        $batchHeader = $this->resourceArray('batch_header');
        $batchId = $batchHeader['payout_batch_id'] ?? null;

        return is_string($batchId) && $batchId !== '' ? $batchId : null;
    }

    public function batchStatus(): ?string
    {
        $batchHeader = $this->resourceArray('batch_header');
        $status = $batchHeader['batch_status'] ?? null;

        return is_string($status) && $status !== '' ? $status : null;
    }
}
