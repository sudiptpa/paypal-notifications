# PayPal Notifications PHP SDK

Framework-agnostic PHP SDK for PayPal **Webhooks** and legacy **Instant Payment Notification (IPN)** verification.

[![CI](https://github.com/sudiptpa/paypal-notifications/actions/workflows/ci.yml/badge.svg)](https://github.com/sudiptpa/paypal-notifications/actions/workflows/ci.yml)
[![Latest Release](https://img.shields.io/github/v/release/sudiptpa/paypal-notifications?sort=semver)](https://github.com/sudiptpa/paypal-notifications/releases)
[![Packagist](https://img.shields.io/packagist/v/sudiptpa/paypal-notifications)](https://packagist.org/packages/sudiptpa/paypal-notifications)
[![PHP](https://img.shields.io/badge/php-8.1--8.5-777bb4.svg)](https://www.php.net/)
[![License](https://img.shields.io/github/license/sudiptpa/paypal-notifications)](LICENSE)

## Table of Contents

- [Why This Package](#why-this-package)
- [Features](#features)
- [Requirements](#requirements)
- [Installation](#installation)
- [Quick Start](#quick-start)
- [Webhook Verification (Simple)](#webhook-verification-simple)
- [Webhook Verification (Advanced)](#webhook-verification-advanced)
- [Typed Event Parsing (Phase 1)](#typed-event-parsing-phase-1)
- [Instant Payment Notification (Legacy)](#instant-payment-notification-legacy)
- [Transport Extension](#transport-extension)
- [Error Handling](#error-handling)
- [Production Checklist](#production-checklist)
- [Testing](#testing)
- [Contributing](#contributing)

## Why This Package

This SDK is built for modern PHP projects that need PayPal notification verification without framework lock-in or heavy HTTP dependencies.

Design goals:

- resource-based API aligned with PayPal docs
- minimal hard dependencies
- explicit models and enums
- transport extensibility
- safe defaults for production usage

## Features

- Webhook signature verification using PayPal `verify-webhook-signature` API
- Case-insensitive extraction of required PayPal headers:
  - `PAYPAL-TRANSMISSION-ID`
  - `PAYPAL-TRANSMISSION-TIME`
  - `PAYPAL-TRANSMISSION-SIG`
  - `PAYPAL-CERT-URL`
  - `PAYPAL-AUTH-ALGO`
- OAuth client credentials flow with in-memory token caching
- Legacy Instant Payment Notification verification (`cmd=_notify-validate`)
- Typed status models:
  - webhooks: `SUCCESS | FAILURE`
  - Instant Payment Notification: `VERIFIED | INVALID | ERROR`
- Native cURL transport included (`CurlTransport`)
- Custom transport support via `TransportInterface`
- Strict exception model and safe error handling

## Requirements

- PHP `^8.1` (supports up to `<8.6`)
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
    ),
    transport: new CurlTransport(),
);
```

## Webhook Verification (Simple)

```php
<?php

declare(strict_types=1);

use Sujip\PayPal\Notifications\Webhook\VerifyWebhookSignatureRequest;

$rawBody = file_get_contents('php://input') ?: '';
$headers = function_exists('getallheaders') ? getallheaders() : $_SERVER;

$request = VerifyWebhookSignatureRequest::fromRawPayload($rawBody, $headers);
$result = $client->webhooks()->verifySignature($request);

if (!$result->isSuccess()) {
    http_response_code(400);
    exit('invalid webhook signature');
}

// Webhook is verified; process the event payload.
```

## Webhook Verification (Advanced)

Use webhook ID override when running multi-tenant webhook endpoints:

```php
$request = VerifyWebhookSignatureRequest::fromRawPayload(
    rawBody: $rawBody,
    headers: $headers,
    webhookId: $resolvedWebhookIdForTenant,
);

$result = $client->webhooks()->verifyWebhookSignature($request);
```

Read debug fields:

```php
$status = $result->status->value;      // SUCCESS | FAILURE
$debugId = $result->debugId;           // PayPal debug ID if present
$raw = $result->rawResponse;           // Raw API response payload
```

## Typed Event Parsing (Phase 1)

Phase 1 introduces a safe typed envelope for all webhook events:

- `webhooks()->parseEvent(array $payload)`
- `webhooks()->parseRawEvent(string $rawBody)`

Current behavior:

- returns `UnknownWebhookEvent` envelope for all event types
- preserves normalized core metadata (`id`, `event_type`, `create_time`, etc.)
- preserves full raw payload for forward compatibility

Example:

```php
<?php

declare(strict_types=1);

$event = $client->webhooks()->parseRawEvent($rawBody);

$eventId = $event->id();
$eventType = $event->eventType();
$resource = $event->resource();
$raw = $event->raw();
```

This gives you stable parsing now, while later phases add dedicated models for high-value PayPal events.

## Instant Payment Notification (Legacy)

Instant Payment Notification is legacy. Keep using it only for existing integrations until fully migrated to Webhooks.

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

You can use any HTTP client stack by implementing this contract:

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

Then inject it into `PayPalClient`.

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

- Always verify webhook signatures before processing event payloads.
- Persist processed webhook event IDs to avoid duplicate processing.
- Use HTTPS endpoint only.
- Keep `clientSecret` outside source control.
- Prefer Webhooks for all new integrations.
- Treat Instant Payment Notification as legacy migration path.

## Testing

```bash
composer install
composer test
```

## Contributing

Contributions are welcome. Include tests for behavior changes.

## License

MIT
