<?php

declare(strict_types=1);

namespace Sujip\PayPal\Notifications\Webhook;

use Sujip\PayPal\Notifications\Webhook\Event\WebhookEventInterface;

final class WebhookEventRouter
{
    /** @var array<string, callable(WebhookEventInterface):mixed> */
    private array $handlers = [];

    /** @var null|callable(WebhookEventInterface):mixed */
    private $fallback = null;

    /**
     * @param callable(WebhookEventInterface):mixed $handler
     */
    public function on(string $eventType, callable $handler): self
    {
        $this->handlers[$eventType] = $handler;

        return $this;
    }

    /**
     * @param callable(WebhookEventInterface):mixed $handler
     */
    public function fallback(callable $handler): self
    {
        $this->fallback = $handler;

        return $this;
    }

    public function dispatch(WebhookEventInterface $event): mixed
    {
        $eventType = $event->eventType();

        if (isset($this->handlers[$eventType])) {
            return ($this->handlers[$eventType])($event);
        }

        if ($this->fallback !== null) {
            return ($this->fallback)($event);
        }

        return null;
    }
}
