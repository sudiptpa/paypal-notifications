# Release Notes v1.1.0

## Highlights

- Added optional persisted OAuth token caching:
  - `TokenCacheInterface`
  - `InMemoryTokenCache`
  - `FileTokenCache`
  - `RedisTokenCache` (via `KeyValueStoreInterface`, no Redis dependency in core)
- Added optional persistent idempotency support:
  - `AtomicIdempotencyStoreInterface`
  - `RedisIdempotencyStore` (via `KeyValueStoreInterface`)
  - `WebhookIdempotencyGuard` now uses atomic `putIfAbsent()` when supported
- Added typed exceptions for safer failure handling:
  - `SignatureVerificationFailed`
  - `TransportFailed`
  - `MalformedPayload`
- Added configurable retry strategy for webhook verification calls:
  - `verificationMaxRetries`
  - `verificationRetryBackoffMs`
  - `verificationRetryMaxBackoffMs`
  - `verificationRetryHttpStatusCodes`
- Expanded typed webhook event coverage across major PayPal families:
  - Payments/Captures (including refunds)
  - Disputes
  - Checkout/Orders
  - Subscriptions
  - Payouts
- Reorganized event implementations by category while preserving backward compatibility through wrappers.
- Added fixture-backed contract tests to lock event mapping behavior.
- Added contributor docs:
  - `ARCHITECTURE.md`
  - `docs/adding-event-mapping.md`
- README updated with production-focused guidance for caching, idempotency, retry strategy, dead-letter flow, and custom transport integration.

## Compatibility

- Public API remains backward compatible.
- No framework dependencies added.
- Core remains transport-agnostic with `CurlTransport` as default.

## Validation

- PHPUnit: pass
- PHPStan: pass
- CI matrix support: PHP 8.2, 8.3, 8.4, 8.5
