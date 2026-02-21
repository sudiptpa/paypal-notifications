# Release Notes v1.0.0

## Highlights

- First stable production release of `sudiptpa/paypal-notifications`.
- PayPal-aligned Webhooks verification flow:
  - required transmission headers
  - OAuth token acquisition via client credentials
  - verify webhook signature API call
- Legacy Instant Payment Notification verification flow:
  - `cmd=_notify-validate`
  - explicit `VERIFIED`, `INVALID`, and `ERROR` outcomes
- Typed webhook event catalog for common high-value event families:
  - captures
  - checkout orders
  - disputes
  - subscriptions
  - payouts
- Unknown event fallback for forward compatibility.
- Event router with enum and convenience mappings.
- Replay-window and cert URL policy controls for stronger runtime safety.
- Framework adapter primitives without framework lock-in.
- Idempotency guard for duplicate webhook prevention.
- High-level webhook processor for structured verification/dispatch results.
- CI and quality baseline:
  - PHPUnit
  - PHPStan
  - CI matrix on PHP 8.2, 8.3, 8.4, 8.5
- Production support files:
  - `README.md`
  - `UPGRADE.md`
  - `CONTRIBUTING.md`
  - `SECURITY.md`
  - `SUPPORT.md`
  - examples and sandbox smoke script

## Upgrade Guide (from `sudiptpa/paypal-ipn`)

1. Replace package dependency:

```bash
composer remove sudiptpa/paypal-ipn
composer require sudiptpa/paypal-notifications
```

2. Initialize `PayPalClient` with explicit config and transport.

3. Move active integrations to Webhooks verification as primary path.

4. Keep Instant Payment Notification only for legacy endpoints still in migration.

## Minimum Recommended Rollout Sequence

1. Deploy with signature verification enabled but side effects disabled in staging.
2. Add idempotency storage before production traffic.
3. Enable strict cert URL policy and replay-window checks.
4. Roll out business handlers event-by-event.
5. Monitor invalid signature rate and unknown event-type counts.

## Validation Summary

- PHPUnit: passing
- PHPStan: passing
- Composer validate: passing

## Notes

- This release is designed to be framework-agnostic and dependency-light.
- For production support issues, include PayPal `debug_id` when available.
