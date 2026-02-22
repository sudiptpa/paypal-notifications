# PayPal Notifications PHP SDK

Framework-agnostic PHP SDK for PayPal **Webhooks** and legacy **Instant Payment Notification (IPN)** verification.

[![CI](https://github.com/sudiptpa/paypal-notifications/actions/workflows/ci.yml/badge.svg)](https://github.com/sudiptpa/paypal-notifications/actions/workflows/ci.yml)
[![Latest Release](https://img.shields.io/github/v/release/sudiptpa/paypal-notifications?sort=semver)](https://github.com/sudiptpa/paypal-notifications/releases)
[![Packagist](https://img.shields.io/packagist/v/sudiptpa/paypal-notifications)](https://packagist.org/packages/sudiptpa/paypal-notifications)
[![Downloads](https://img.shields.io/packagist/dt/sudiptpa/paypal-notifications)](https://packagist.org/packages/sudiptpa/paypal-notifications)
[![PHP](https://img.shields.io/badge/php-8.2--8.5-777bb4.svg)](https://www.php.net/)
[![License](https://img.shields.io/github/license/sudiptpa/paypal-notifications)](LICENSE)

## Table of Contents

- [Why This Package](#why-this-package)
- [Features](#features)
- [Requirements](#requirements)
- [Installation](#installation)
- [Quick Start](#quick-start)
- [Release Highlights (v1.0.0)](#release-highlights-v100)
- [Webhook Verification (Simple)](#webhook-verification-simple)
- [Webhook Verification (Advanced)](#webhook-verification-advanced)
- [Typed Event Parsing](#typed-event-parsing)
- [Event Catalog](#event-catalog)
- [Event Routing](#event-routing)
- [Framework Adapters](#framework-adapters)
- [Webhook Processor](#webhook-processor)
- [Idempotency Guard](#idempotency-guard)
- [Instant Payment Notification (Legacy)](#instant-payment-notification-legacy)
- [Transport Extension](#transport-extension)
- [Examples](#examples)
- [Error Handling](#error-handling)
- [Production Checklist](#production-checklist)
- [Testing](#testing)
- [Manual Sandbox Smoke Test](#manual-sandbox-smoke-test)
- [Contributing](#contributing)

## Why This Package

This SDK is for PHP projects that need reliable PayPal notification verification without framework lock-in.

Design goals:

- resource-based API aligned with PayPal docs
- minimal hard dependencies
- explicit models and enums
- transport extensibility
- safe production defaults

## Features

- Webhook signature verification using PayPal `verify-webhook-signature` API
- Case-insensitive extraction of required PayPal headers:
  - `PAYPAL-TRANSMISSION-ID`
  - `PAYPAL-TRANSMISSION-TIME`
  - `PAYPAL-TRANSMISSION-SIG`
  - `PAYPAL-CERT-URL`
  - `PAYPAL-AUTH-ALGO`
- OAuth client credentials flow with in-memory token caching
- Typed event parsing with mapped models for:
  - `PAYMENT.CAPTURE.COMPLETED`
  - `PAYMENT.CAPTURE.DENIED`
  - `PAYMENT.CAPTURE.REFUNDED`
  - `PAYMENT.CAPTURE.PENDING`
  - `PAYMENT.CAPTURE.REVERSED`
  - `CHECKOUT.ORDER.APPROVED`
  - `CHECKOUT.ORDER.COMPLETED`
  - `CUSTOMER.DISPUTE.CREATED`
  - `CUSTOMER.DISPUTE.RESOLVED`
  - `BILLING.SUBSCRIPTION.CREATED`
  - `BILLING.SUBSCRIPTION.CANCELLED`
  - `BILLING.SUBSCRIPTION.ACTIVATED`
  - `BILLING.SUBSCRIPTION.SUSPENDED`
  - `BILLING.SUBSCRIPTION.EXPIRED`
  - `BILLING.SUBSCRIPTION.PAYMENT.FAILED`
  - `PAYMENT.PAYOUTSBATCH.SUCCESS`
  - `PAYMENT.PAYOUTS-ITEM.SUCCEEDED`
  - `PAYMENT.PAYOUTS-ITEM.DENIED`
- Unknown event fallback (`UnknownWebhookEvent`) for forward compatibility
- Event router helper for clean application handlers
- Framework adapter contract for framework-specific request bridges
- High-level `WebhookProcessor` with structured processing result (easy to log and monitor)
- Idempotency guard support for duplicate event prevention
- Legacy Instant Payment Notification verification (`cmd=_notify-validate`)
- Native cURL transport included (`CurlTransport`)
- Custom transport support via `TransportInterface`
- Strict exception model and safe error handling

## Requirements

- PHP `^8.2` (supports up to `<8.6`)
- `ext-json`
- `ext-curl`

## Installation

```bash
composer require sudiptpa/paypal-notifications
```

## Quick Start

```php
<?php

declare(strict_types=1);

use Sujip\PayPal\Notifications\Config\ClientConfig;
use Sujip\PayPal\Notifications\Config\Environment;
use Sujip\PayPal\Notifications\PayPalClient;
use Sujip\PayPal\Notifications\Transport\CurlTransport;

$client = new PayPalClient(
    config: new ClientConfig(
        clientId: $_ENV['PAYPAL_CLIENT_ID'],
        clientSecret: $_ENV['PAYPAL_CLIENT_SECRET'],
        webhookId: $_ENV['PAYPAL_WEBHOOK_ID'],
        environment: Environment::Sandbox,
        maxWebhookTransmissionAgeSeconds: 300,
        allowedWebhookClockSkewSeconds: 30,
        strictPayPalCertUrlValidation: true,
    ),
    transport: new CurlTransport(),
);
```

## Release Highlights (v1.0.0)

- PayPal Webhooks signature verification with PayPal-aligned request fields and validation flow.
- Legacy Instant Payment Notification support maintained for migration-safe integrations.
- Typed webhook events and enum-driven routing helpers for cleaner handlers.
- Idempotency guard support to reduce duplicate webhook side effects.
- Replay-window and cert URL policy controls for stronger production security.
- Framework-agnostic adapters for request extraction without framework lock-in.
- Native cURL transport plus extension interface for custom HTTP stacks.
- CI-validated on PHP 8.2, 8.3, 8.4, and 8.5.

## Webhook Verification (Simple)

```php
<?php

declare(strict_types=1);

use Sujip\PayPal\Notifications\Webhook\VerifyWebhookSignatureRequest;

$rawBody = file_get_contents('php://input') ?: '';
$headers = function_exists('getallheaders') ? getallheaders() : $_SERVER;

$request = $client->webhooks()->requestFromRawPayload($rawBody, $headers);
$result = $client->webhooks()->verifySignature($request);

if (!$result->isSuccess()) {
    http_response_code(400);
    exit('invalid webhook signature');
}

// Webhook is verified; continue with event parsing/handling.
```

## Webhook Verification (Advanced)

Use webhook ID override for multi-tenant endpoints:

```php
$request = VerifyWebhookSignatureRequest::fromRawPayload(
    rawBody: $rawBody,
    headers: $headers,
    webhookId: $resolvedWebhookId,
);

$result = $client->webhooks()->verifyWebhookSignature($request);
```

Inspect debug fields:

```php
$status = $result->status->value;      // SUCCESS | FAILURE
$debugId = $result->debugId;           // PayPal debug ID if present
$raw = $result->rawResponse;           // Raw API response payload
```

## Typed Event Parsing

Parse to typed models from payload array or raw JSON:

```php
$event = $client->webhooks()->parseRawEvent($rawBody);

if ($event->is('PAYMENT.CAPTURE.COMPLETED')) {
    // typed model returned for mapped event type
}
```

Mapped event models:

- `PaymentCaptureCompletedEvent`
- `PaymentCaptureDeniedEvent`
- `PaymentCaptureRefundedEvent`
- `PaymentCapturePendingEvent`
- `PaymentCaptureReversedEvent`
- `CheckoutOrderApprovedEvent`
- `CheckoutOrderCompletedEvent`
- `CustomerDisputeCreatedEvent`
- `CustomerDisputeResolvedEvent`
- `BillingSubscriptionCreatedEvent`
- `BillingSubscriptionCancelledEvent`
- `BillingSubscriptionActivatedEvent`
- `BillingSubscriptionSuspendedEvent`
- `BillingSubscriptionExpiredEvent`
- `BillingSubscriptionPaymentFailedEvent`
- `PaymentPayoutsBatchSuccessEvent`
- `PaymentPayoutsItemSucceededEvent`
- `PaymentPayoutsItemDeniedEvent`

Unmapped events return `UnknownWebhookEvent` and keep the full raw payload.

## Event Catalog

| PayPal Event Type | Typed Class |
| --- | --- |
| `PAYMENT.CAPTURE.COMPLETED` | `PaymentCaptureCompletedEvent` |
| `PAYMENT.CAPTURE.DENIED` | `PaymentCaptureDeniedEvent` |
| `PAYMENT.CAPTURE.REFUNDED` | `PaymentCaptureRefundedEvent` |
| `PAYMENT.CAPTURE.PENDING` | `PaymentCapturePendingEvent` |
| `PAYMENT.CAPTURE.REVERSED` | `PaymentCaptureReversedEvent` |
| `CHECKOUT.ORDER.APPROVED` | `CheckoutOrderApprovedEvent` |
| `CHECKOUT.ORDER.COMPLETED` | `CheckoutOrderCompletedEvent` |
| `CUSTOMER.DISPUTE.CREATED` | `CustomerDisputeCreatedEvent` |
| `CUSTOMER.DISPUTE.RESOLVED` | `CustomerDisputeResolvedEvent` |
| `BILLING.SUBSCRIPTION.CREATED` | `BillingSubscriptionCreatedEvent` |
| `BILLING.SUBSCRIPTION.CANCELLED` | `BillingSubscriptionCancelledEvent` |
| `BILLING.SUBSCRIPTION.ACTIVATED` | `BillingSubscriptionActivatedEvent` |
| `BILLING.SUBSCRIPTION.SUSPENDED` | `BillingSubscriptionSuspendedEvent` |
| `BILLING.SUBSCRIPTION.EXPIRED` | `BillingSubscriptionExpiredEvent` |
| `BILLING.SUBSCRIPTION.PAYMENT.FAILED` | `BillingSubscriptionPaymentFailedEvent` |
| `PAYMENT.PAYOUTSBATCH.SUCCESS` | `PaymentPayoutsBatchSuccessEvent` |
| `PAYMENT.PAYOUTS-ITEM.SUCCEEDED` | `PaymentPayoutsItemSucceededEvent` |
| `PAYMENT.PAYOUTS-ITEM.DENIED` | `PaymentPayoutsItemDeniedEvent` |

## Event Routing

Use `WebhookEventRouter` to map event types to handlers:

```php
use Sujip\PayPal\Notifications\Webhook\Event\WebhookEventType;
use Sujip\PayPal\Notifications\Webhook\WebhookEventRouter;

$router = (new WebhookEventRouter())
    ->onCaptureCompleted(function ($event) {
        // handle capture completed
    })
    ->onType(WebhookEventType::CustomerDisputeCreated, function ($event) {
        // handle dispute created
    })
    ->onSubscriptionPaymentFailed(function ($event) {
        // handle subscription payment failed
    })
    ->fallback(function ($event) {
        // log/ignore unknown event types
    });

$router->dispatch($event);
```

## Framework Adapters

Use `WebhookRequestAdapterInterface` to bridge framework request objects without adding framework dependencies to this package.

```php
use Sujip\PayPal\Notifications\Contracts\WebhookRequestAdapterInterface;

final readonly class LaravelWebhookAdapter implements WebhookRequestAdapterInterface
{
    public function __construct(private \Illuminate\Http\Request $request)
    {
    }

    public function rawBody(): string
    {
        return (string) $this->request->getContent();
    }

    public function headers(): array
    {
        return $this->request->headers->all();
    }

    public function webhookId(): ?string
    {
        return config('services.paypal.webhook_id');
    }
}
```

Built-in generic adapters:

- `ArrayWebhookRequestAdapter`
- `SuperglobalWebhookRequestAdapter`

## Webhook Processor

`WebhookProcessor` handles the full flow: request extraction -> signature verification -> event parsing -> optional idempotency -> optional routing -> structured result.

```php
use Sujip\PayPal\Notifications\Adapter\SuperglobalWebhookRequestAdapter;
use Sujip\PayPal\Notifications\Idempotency\InMemoryIdempotencyStore;
use Sujip\PayPal\Notifications\Idempotency\WebhookIdempotencyGuard;
use Sujip\PayPal\Notifications\Webhook\WebhookEventRouter;

$router = (new WebhookEventRouter())->onCaptureCompleted(fn () => null);
$guard = new WebhookIdempotencyGuard(new InMemoryIdempotencyStore());

$result = $client->webhookProcessor($router, $guard)->process(
    SuperglobalWebhookRequestAdapter::fromGlobals()
);

if (!$result->accepted) {
    http_response_code(400);
}
```

## Idempotency Guard

Use idempotency to avoid duplicate webhook processing:

```php
use Sujip\PayPal\Notifications\Idempotency\InMemoryIdempotencyStore;
use Sujip\PayPal\Notifications\Idempotency\WebhookIdempotencyGuard;

$guard = new WebhookIdempotencyGuard(new InMemoryIdempotencyStore());

if (!$guard->checkAndRemember($event)) {
    // duplicate or missing event ID -> skip processing
    return;
}

// process event exactly once in this store scope
```

For production, implement `IdempotencyStoreInterface` with persistent storage (Redis, DB, cache).

## Instant Payment Notification (Legacy)

Instant Payment Notification is legacy. Keep using it only for existing integrations while migrating to Webhooks.

Verify from array payload:

```php
<?php

declare(strict_types=1);

use Sujip\PayPal\Notifications\InstantPaymentNotification\VerifyInstantPaymentNotificationRequest;

$result = $client->ipn()->verify(
    VerifyInstantPaymentNotificationRequest::fromArray($_POST)
);

if ($result->isVerified()) {
    // VERIFIED
}
```

Verify from raw body:

```php
$raw = file_get_contents('php://input') ?: '';
$result = $client->instantPaymentNotification()->verifyRaw($raw);
```

## Transport Extension

Use any HTTP client stack by implementing this contract:

```php
use Sujip\PayPal\Notifications\Contracts\TransportInterface;
use Sujip\PayPal\Notifications\Http\HttpRequest;
use Sujip\PayPal\Notifications\Http\HttpResponse;

final class CustomTransport implements TransportInterface
{
    public function send(HttpRequest $request): HttpResponse
    {
        // call your preferred HTTP client and map response
    }
}
```

Inject custom transport into `PayPalClient`.

## Examples

- `examples/webhook-endpoint.php` - full webhook verification + event routing flow.
- `examples/ipn-endpoint.php` - legacy Instant Payment Notification verification endpoint.
- `examples/custom-transport.php` - transport contract integration template.
- `examples/framework-adapter-template.php` - framework adapter contract template.

## Error Handling

Main exceptions:

- `ConfigurationException`
- `TransportException`
- `AuthenticationException`
- `InvalidWebhookHeadersException`
- `InvalidPayloadException`
- `VerificationException`

Example:

```php
try {
    $result = $client->webhooks()->verifySignature($request);
} catch (\Sujip\PayPal\Notifications\Exception\PayPalException $e) {
    // log and fail closed
}
```

## Production Checklist

- Configure a unique webhook endpoint per environment (sandbox and live).
- Verify webhook signatures before any business logic.
- Set `maxWebhookTransmissionAgeSeconds` and `allowedWebhookClockSkewSeconds` for replay protection.
- Keep `strictPayPalCertUrlValidation` enabled in production.
- Persist webhook event IDs in a durable store and enable idempotency guard.
- Handle verification failures with fail-closed behavior (return non-2xx on invalid signatures).
- Keep `clientSecret` in a secure secret store; never commit credentials.
- Log PayPal `debug_id` for failed verification calls to simplify support investigations.
- Monitor duplicate, failed, and unknown event-type counts.
- Prefer Webhooks for all new integrations; treat Instant Payment Notification as legacy-only.

## Testing

```bash
composer install
composer test
composer stan
```

## Manual Sandbox Smoke Test

Use `scripts/smoke/sandbox-webhook-smoke.php` to call your local webhook endpoint with controlled headers and payloads while validating your integration.

```bash
php scripts/smoke/sandbox-webhook-smoke.php \
  --url="http://127.0.0.1:8000/webhook/paypal" \
  --payload='{"id":"WH-TEST","event_type":"PAYMENT.CAPTURE.COMPLETED","resource":{"id":"CAP-1"}}' \
  --header="PAYPAL-TRANSMISSION-ID: trans-1" \
  --header="PAYPAL-TRANSMISSION-TIME: 2026-02-21T00:00:00Z" \
  --header="PAYPAL-TRANSMISSION-SIG: sig" \
  --header="PAYPAL-CERT-URL: https://api-m.sandbox.paypal.com/v1/notifications/certs/CERT-123" \
  --header="PAYPAL-AUTH-ALGO: SHA256withRSA"
```

## Contributing

Contributions are welcome. Please include tests for behavior changes.

See `CONTRIBUTING.md` for contributor workflow, `SUPPORT.md` for support flow, and `SECURITY.md` for vulnerability reporting.

## License

MIT
