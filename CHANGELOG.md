# Changelog

All notable changes to this project will be documented in this file.

## [Unreleased]

### Added

- `TokenCacheInterface` with reference implementations:
  - `InMemoryTokenCache`
  - `FileTokenCache`
  - `RedisTokenCache` (via `KeyValueStoreInterface`, no Redis dependency required)
- `KeyValueStoreInterface` for optional distributed cache integrations.
- Persistent idempotency store references:
  - `RedisIdempotencyStore` (via `KeyValueStoreInterface`)
  - app-managed database store via `IdempotencyStoreInterface`
- `AtomicIdempotencyStoreInterface` and atomic path in `WebhookIdempotencyGuard`.
- New typed exceptions:
  - `SignatureVerificationFailed`
  - `TransportFailed`
  - `MalformedPayload`
- Configurable webhook verification retry controls in `ClientConfig`:
  - `verificationMaxRetries`
  - `verificationRetryBackoffMs`
  - `verificationRetryMaxBackoffMs`
  - `verificationRetryHttpStatusCodes`
- `ARCHITECTURE.md` with contributor-oriented package map and extension guide.

### Changed

- `OAuthTokenProvider` now supports optional persisted token cache injection.
- `PayPalClient` accepts optional `TokenCacheInterface` in constructor.
- Webhook verification flow now supports conservative retries for transient failures.
- Event files are now organized by PayPal categories under `src/Webhook/Event/`:
  - `Payments/`
  - `Disputes/`
  - `Orders/`
  - `Subscriptions/`
  - `Payouts/`
- README expanded with caching, persistent idempotency, retry strategy, dead-letter guidance, and updated exception list.

### Tested

- Added unit tests for:
  - `FileTokenCache`
  - `RedisTokenCache`
  - `RedisIdempotencyStore`
- Added integration-like webhook verification tests for retry and typed failure modes.

## [1.0.0] - 2026-02-21

### Added

- Initial stable production release of `sudiptpa/paypal-notifications`
- PHP baseline standardized to 8.2+ with support through 8.5.
- PayPal Webhooks signature verification aligned with PayPal Verify Webhook Signature flow.
- OAuth token acquisition with in-memory token caching.
- Legacy Instant Payment Notification verification module with modernized APIs.
- Resource-first public API:
  - `webhooks()->verifySignature(...)`
  - `ipn()->verify(...)`
  - `instantPaymentNotification()->verify(...)`
- Dependency-light core:
  - native `CurlTransport`
  - transport extension via `TransportInterface`
  - no hard dependency on framework or external HTTP clients
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
- Explicit exception model and safer error boundaries:
  - strict webhook header and payload validation
  - explicit non-2xx verification failure handling
  - transport and OAuth failure mapping to dedicated exception paths
  - OAuth transport-failure mapping to authentication exceptions
- Tests for event envelope parsing and invalid raw-event payload handling.
- Tests for typed event mapping, router dispatching, and idempotency guard behavior.
- Tooling and quality baseline:
  - PHPUnit test suite
  - PHPStan static analysis
  - GitHub Actions CI matrix for PHP 8.2, 8.3, 8.4, and 8.5
  - lowest-dependency install coverage
  - CI runs PHPUnit plus syntax lint across `src/` and `tests/`
- PHP 8.5-safe cURL handle close behavior.
- Added `SECURITY.md`, `SUPPORT.md`, and issue templates for production OSS maintenance.
- Added `CONTRIBUTING.md` with development and PR workflow.
- Added `.gitattributes` export-ignore rules for cleaner package distributions.
- Added `examples/` for webhook endpoint, IPN endpoint, and custom transport integration.
- Added `scripts/smoke/sandbox-webhook-smoke.php` for manual sandbox endpoint checks.
- Production-focused README with simple-to-advanced usage.
- Added release notes and upgrade documentation.
