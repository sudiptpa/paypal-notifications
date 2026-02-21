# Changelog

All notable changes to this project will be documented in this file.

## [Unreleased]

### Added

- PHP baseline standardized to 8.2+ with support through 8.5.
- Typed webhook event envelope support:
  - `WebhookEventInterface`
  - `UnknownWebhookEvent`
  - `EventFactory`
  - `WebhooksResource::parseEvent(...)`
  - `WebhooksResource::parseRawEvent(...)`
- Typed mapped webhook events:
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
- `WebhookEventType` enum for discoverable event constants.
- `WebhookEventRouter` for event-type based handler dispatching with fallback support.
- Router ergonomics:
  - `onType(WebhookEventType::...)`
  - `onCaptureCompleted(...)`
  - `onCaptureRefunded(...)`
  - `onDisputeCreated(...)`
  - `onSubscriptionPaymentFailed(...)`
- Idempotency helpers:
  - `IdempotencyStoreInterface`
  - `InMemoryIdempotencyStore`
  - `WebhookIdempotencyGuard`
- Webhook verification hardening:
  - replay-window validation via `maxWebhookTransmissionAgeSeconds`
  - configurable future skew tolerance via `allowedWebhookClockSkewSeconds`
  - configurable cert URL strictness via `strictPayPalCertUrlValidation`
  - configurable trusted cert host suffixes, cert path prefix, and HTTPS-port policy
  - `WebhooksResource::requestFromRawPayload(...)` to ensure config-aligned request creation
- Framework adapter primitives:
  - `WebhookRequestAdapterInterface`
  - `ArrayWebhookRequestAdapter`
  - `SuperglobalWebhookRequestAdapter`
  - `WebhooksResource::requestFromAdapter(...)`
- Structured observability workflow:
  - `WebhookProcessor`
  - `WebhookProcessingResult`
  - `WebhookObserverInterface` + `NullWebhookObserver`
- Tests for event envelope parsing and invalid raw-event payload handling.
- Tests for typed event mapping, router dispatching, and idempotency guard behavior.
- Added `SECURITY.md`, `SUPPORT.md`, and issue templates for production OSS maintenance.
- Added `examples/` for webhook endpoint, IPN endpoint, and custom transport integration.
- CI now runs PHPUnit plus PHP syntax lint (`php -l`) across `src/` and `tests/`.

## [1.0.0] - 2026-02-21

### Added

- Initial production release of `sudiptpa/paypal-notifications`
- Webhooks Verify Signature flow with OAuth token handling
- In-memory OAuth token caching
- Legacy Instant Payment Notification verification module
- Dependency-light architecture with native `CurlTransport`
- Transport extension contract for custom HTTP adapters
- Typed request/response models, enums, and explicit exceptions
- PHPUnit test suite with unit and integration-like coverage
- GitHub Actions CI matrix (PHP 8.2-8.5) with lowest-dependency run
- Strict webhook `cert_url` validation (`https` + PayPal domain)
- Explicit non-2xx webhook verification failure handling
- OAuth transport-failure mapping to authentication exceptions
- PHP 8.5-safe cURL handle close behavior
- Production-focused README with simple-to-advanced usage
