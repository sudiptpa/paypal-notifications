<?php

declare(strict_types=1);

namespace Sujip\PayPal\Notifications\Webhook;

use Sujip\PayPal\Notifications\Contracts\WebhookObserverInterface;
use Sujip\PayPal\Notifications\Contracts\WebhookRequestAdapterInterface;
use Sujip\PayPal\Notifications\Idempotency\WebhookIdempotencyGuard;
use Sujip\PayPal\Notifications\Log\NullWebhookObserver;
use Sujip\PayPal\Notifications\Resource\WebhooksResource;

final class WebhookProcessor
{
    public function __construct(
        private readonly WebhooksResource $webhooks,
        private readonly ?WebhookEventRouter $router = null,
        private readonly ?WebhookIdempotencyGuard $idempotencyGuard = null,
        ?WebhookObserverInterface $observer = null,
    ) {
        $this->observer = $observer ?? new NullWebhookObserver();
    }

    private readonly WebhookObserverInterface $observer;

    public function process(WebhookRequestAdapterInterface $adapter): WebhookProcessingResult
    {
        $startedAt = microtime(true);

        try {
            $request = $this->webhooks->requestFromAdapter($adapter);
            $verification = $this->webhooks->verifySignature($request);

            if ($verification->isFailure()) {
                $result = $this->result(
                    startedAt: $startedAt,
                    accepted: false,
                    dispatched: false,
                    duplicate: false,
                    eventId: null,
                    eventType: null,
                    verificationStatus: $verification->status,
                    message: 'PayPal signature verification failed.',
                );
                $this->observer->record($result);

                return $result;
            }

            $event = $this->webhooks->parseRawEvent($adapter->rawBody());

            if ($this->idempotencyGuard !== null && !$this->idempotencyGuard->checkAndRemember($event)) {
                $result = $this->result(
                    startedAt: $startedAt,
                    accepted: true,
                    dispatched: false,
                    duplicate: true,
                    eventId: $event->id(),
                    eventType: $event->eventType(),
                    verificationStatus: $verification->status,
                    message: 'Duplicate webhook event skipped by idempotency guard.',
                );
                $this->observer->record($result);

                return $result;
            }

            $dispatched = false;
            if ($this->router !== null) {
                $this->router->dispatch($event);
                $dispatched = true;
            }

            $result = $this->result(
                startedAt: $startedAt,
                accepted: true,
                dispatched: $dispatched,
                duplicate: false,
                eventId: $event->id(),
                eventType: $event->eventType(),
                verificationStatus: $verification->status,
                message: 'Webhook processed successfully.',
            );
            $this->observer->record($result);

            return $result;
        } catch (\Throwable $exception) {
            $result = $this->result(
                startedAt: $startedAt,
                accepted: false,
                dispatched: false,
                duplicate: false,
                eventId: null,
                eventType: null,
                verificationStatus: null,
                message: $exception->getMessage(),
                context: ['exception' => $exception::class],
            );
            $this->observer->record($result);

            return $result;
        }
    }

    /**
     * @param array<string,mixed> $context
     */
    private function result(
        float $startedAt,
        bool $accepted,
        bool $dispatched,
        bool $duplicate,
        ?string $eventId,
        ?string $eventType,
        ?\Sujip\PayPal\Notifications\Enum\VerificationStatus $verificationStatus,
        string $message,
        array $context = [],
    ): WebhookProcessingResult {
        $durationMs = (int) round((microtime(true) - $startedAt) * 1000);

        return new WebhookProcessingResult(
            accepted: $accepted,
            dispatched: $dispatched,
            duplicate: $duplicate,
            eventId: $eventId,
            eventType: $eventType,
            verificationStatus: $verificationStatus,
            durationMs: max(0, $durationMs),
            message: $message,
            context: $context,
        );
    }
}
