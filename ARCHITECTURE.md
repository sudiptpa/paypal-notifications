# Architecture Guide

This document explains how `sudiptpa/paypal-notifications` is structured, where each responsibility lives, and how contributors can extend the package safely.

## Design Goals

- Framework-agnostic core
- Minimal required dependencies
- Explicit typed models and exceptions
- Security-first webhook verification flow
- Extension points via small contracts

## Project Map

```text
src/
  Adapter/                      Request adapter helpers (array/superglobals)
  Auth/                         OAuth token lifecycle + token cache interfaces
  Config/                       Client and environment configuration
  Contracts/                    Core extension contracts
  Enum/                         Public enums
  Exception/                    Typed exception hierarchy
  Http/                         Internal request/response DTOs for transport
  Idempotency/                  Duplicate-event protection abstractions/stores
  InstantPaymentNotification/   Legacy Instant Payment Notification models
  Log/                          No-op logger/observer implementations
  Resource/                     Main API resources (Webhooks, IPN)
  Transport/                    Default CurlTransport
  Webhook/                      Webhook request/result/processor/router/events
    Event/
      Payments/                 Capture/refund related events
      Disputes/                 Customer dispute events
      Orders/                   Checkout order events
      Subscriptions/            Billing subscription events
      Payouts/                  Payout batch/item events
```

## Runtime Flow

### 1) Webhook Verification

1. Incoming payload + headers are normalized into `VerifyWebhookSignatureRequest`.
2. `WebhooksResource::verifySignature()` validates replay window and cert URL policy.
3. OAuth token is resolved by `OAuthTokenProvider` (memory cache + optional persisted cache).
4. Verification request is sent to PayPal via `TransportInterface`.
5. Response is mapped to `VerifyWebhookSignatureResult`.
6. `WebhookProcessor` can continue with event parsing, idempotency, and routing.

### 2) Event Parsing

1. `parseRawEvent()` decodes JSON payload.
2. `EventFactory` maps `event_type` to typed event classes.
3. Unmapped events become `UnknownWebhookEvent`.

### 3) Idempotency

1. `WebhookIdempotencyGuard` checks event ID.
2. If store supports atomic writes (`AtomicIdempotencyStoreInterface`), it uses `putIfAbsent()`.
3. Otherwise it falls back to `has()` then `put()`.

## Extension Points

## `TransportInterface`

Implement this to use your own HTTP stack (Guzzle, Symfony, Laravel HTTP, etc.).

## `KeyValueStoreInterface`

Implement this for Redis or distributed cache backends and wire it into:

- `RedisTokenCache`
- `RedisIdempotencyStore`

## `TokenCacheInterface`

Custom OAuth token persistence strategies can be plugged into `PayPalClient`.

## Event Extension

To add a new typed webhook event:

1. Add a class under the category folder in `src/Webhook/Event/` (for example `Payments/`, `Disputes/`, `Orders/`, `Subscriptions/`, `Payouts/`).
2. Implement typed accessors from `resource`.
3. Register mapping in `EventFactory`.
4. Add fixtures/tests in `tests/Unit/EventFactoryTest.php` and parsing tests.

## Safety Boundaries

- Never log secrets (`clientSecret`, bearer tokens).
- Fail closed on signature verification failure.
- Keep cert URL validation strict in production.
- Keep retry defaults conservative (`verificationMaxRetries = 0`).

## Contributor Checklist

1. Add/modify code.
2. Add or update tests.
3. Run:
   - `composer test`
   - `composer stan`
4. Update docs (`README.md`, `CHANGELOG.md`, this file) when behavior changes.
