<?php

declare(strict_types=1);

namespace Sujip\PayPal\Notifications\Idempotency;

use Sujip\PayPal\Notifications\Webhook\Event\WebhookEventInterface;

final class WebhookIdempotencyGuard
{
    public function __construct(private readonly IdempotencyStoreInterface $store)
    {
    }

    public function checkAndRemember(WebhookEventInterface $event): bool
    {
        $eventId = trim($event->id());

        if ($eventId === '') {
            return false;
        }

        if ($this->store instanceof AtomicIdempotencyStoreInterface) {
            return $this->store->putIfAbsent($eventId);
        }

        if ($this->store->has($eventId)) {
            return false;
        }

        $this->store->put($eventId);

        return true;
    }
}
