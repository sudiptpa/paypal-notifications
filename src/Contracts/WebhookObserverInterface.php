<?php

declare(strict_types=1);

namespace Sujip\PayPal\Notifications\Contracts;

use Sujip\PayPal\Notifications\Webhook\WebhookProcessingResult;

interface WebhookObserverInterface
{
    public function record(WebhookProcessingResult $result): void;
}
