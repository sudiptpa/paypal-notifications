# Release Notes v1.1.0

## Highlights

- Expanded typed webhook event coverage across:
  - captures
  - checkout orders
  - disputes
  - subscriptions
  - payouts
- Added `WebhookEventType` enum for discoverable event constants.
- Added router ergonomics:
  - `onType(WebhookEventType::...)`
  - `onCaptureCompleted(...)`
  - `onCaptureRefunded(...)`
  - `onDisputeCreated(...)`
  - `onSubscriptionPaymentFailed(...)`
- Added webhook replay protection support with configurable window/skew.
- Added hardened cert URL policy controls:
  - trusted host suffixes
  - required certificate path prefix
  - strict HTTPS-port policy
- Added config-aligned request helper:
  - `WebhooksResource::requestFromRawPayload(...)`
- Added framework adapter primitives:
  - `WebhookRequestAdapterInterface`
  - `ArrayWebhookRequestAdapter`
  - `SuperglobalWebhookRequestAdapter`
  - `WebhooksResource::requestFromAdapter(...)`
- Added `WebhookProcessor` + `WebhookProcessingResult` for structured observability.
- Added `WebhookProcessor` + `WebhookProcessingResult` so webhook handling outcomes are easier to track.
- Added PHPStan static analysis tooling + CI execution.
- Added contributor workflow guide (`CONTRIBUTING.md`) and export-ignore policy (`.gitattributes`).
- Added a manual sandbox smoke test helper script for endpoint verification.
- Added production OSS files: `SECURITY.md`, `SUPPORT.md`, issue templates.
- Added runnable `examples/` for webhook, IPN, custom transport, and adapter template.

## Upgrade Guide

1. Update package:

```bash
composer update sudiptpa/paypal-notifications
```

2. Prefer config-aligned webhook request creation:

```php
$request = $client->webhooks()->requestFromRawPayload($rawBody, $headers);
```

3. Configure replay validation:

```php
new ClientConfig(
    clientId: '...',
    clientSecret: '...',
    webhookId: '...',
    maxWebhookTransmissionAgeSeconds: 300,
    allowedWebhookClockSkewSeconds: 30,
);
```

4. Switch event string literals to enum constants where possible:

```php
$router->onType(WebhookEventType::CustomerDisputeCreated, $handler);
```

## Validation

- PHPUnit: passing
- PHP lint: passing
- Composer validate: passing
