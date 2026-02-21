<?php

declare(strict_types=1);

namespace Sujip\PayPal\Notifications\Tests\Fakes;

use Sujip\PayPal\Notifications\Contracts\WebhookObserverInterface;
use Sujip\PayPal\Notifications\Webhook\WebhookProcessingResult;

final class InMemoryWebhookObserver implements WebhookObserverInterface
{
    /** @var list<WebhookProcessingResult> */
    public array $records = [];

    public function record(WebhookProcessingResult $result): void
    {
        $this->records[] = $result;
    }
}
