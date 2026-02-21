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
    public function onType(\Sujip\PayPal\Notifications\Webhook\Event\WebhookEventType $eventType, callable $handler): self
    {
        return $this->on($eventType->value, $handler);
    }

    /**
     * @param callable(WebhookEventInterface):mixed $handler
     */
    public function onCaptureCompleted(callable $handler): self
    {
        return $this->onType(\Sujip\PayPal\Notifications\Webhook\Event\WebhookEventType::PaymentCaptureCompleted, $handler);
    }

    /**
     * @param callable(WebhookEventInterface):mixed $handler
     */
    public function onCaptureRefunded(callable $handler): self
    {
        return $this->onType(\Sujip\PayPal\Notifications\Webhook\Event\WebhookEventType::PaymentCaptureRefunded, $handler);
    }

    /**
     * @param callable(WebhookEventInterface):mixed $handler
     */
    public function onDisputeCreated(callable $handler): self
    {
        return $this->onType(\Sujip\PayPal\Notifications\Webhook\Event\WebhookEventType::CustomerDisputeCreated, $handler);
    }

    /**
     * @param callable(WebhookEventInterface):mixed $handler
     */
    public function onSubscriptionPaymentFailed(callable $handler): self
    {
        return $this->onType(
            \Sujip\PayPal\Notifications\Webhook\Event\WebhookEventType::BillingSubscriptionPaymentFailed,
            $handler
        );
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
