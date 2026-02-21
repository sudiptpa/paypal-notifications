<?php

declare(strict_types=1);

namespace Sujip\PayPal\Notifications\Log;

use Sujip\PayPal\Notifications\Contracts\WebhookObserverInterface;
use Sujip\PayPal\Notifications\Webhook\WebhookProcessingResult;

final class NullWebhookObserver implements WebhookObserverInterface
{
    public function record(WebhookProcessingResult $result): void
    {
    }
}
