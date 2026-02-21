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
- Added config-aligned request helper:
  - `WebhooksResource::requestFromRawPayload(...)`
- Added production OSS files: `SECURITY.md`, `SUPPORT.md`, issue templates.
- Added runnable `examples/` for webhook, IPN, and custom transport.

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
