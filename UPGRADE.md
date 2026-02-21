# Upgrade Guide

## From `sudiptpa/paypal-ipn`

`sudiptpa/paypal-notifications` is the modern replacement package.

## API Mapping

- Old listener/event style (`Sujip\PayPal\Notification\...`) -> new resource API with explicit request/response models.
- Old IPN verification flow -> `$client->ipn()->verify(...)` using `VerifyInstantPaymentNotificationRequest`.
- New primary flow (recommended) -> `$client->webhooks()->verifySignature(...)`.

## Migration Steps

1. Replace package dependency:
   - remove: `sudiptpa/paypal-ipn`
   - add: `sudiptpa/paypal-notifications`
2. Initialize `PayPalClient` with `ClientConfig` + `CurlTransport`.
3. Move webhook endpoints to PayPal verify-webhook-signature flow.
4. Keep Instant Payment Notification only for legacy endpoints while migrating.

## Benefits After Migration

- No hard dependency on Guzzle, Symfony, or Laravel.
- Transport extension via a small contract.
- Typed enums and result models.
- Typed webhook event parsing (`parseEvent` / `parseRawEvent`) with safe unknown fallback.
- Optional event routing and idempotency helpers for production webhook consumers.
- Webhook verification safety knobs in `ClientConfig`:
  - `maxWebhookTransmissionAgeSeconds`
  - `allowedWebhookClockSkewSeconds`
  - `strictPayPalCertUrlValidation`
  - `trustedWebhookCertHostSuffixes`
  - `requiredWebhookCertPathPrefix`
  - `requireDefaultHttpsPortForWebhookCertUrl`
- Config-aligned request creation via `$client->webhooks()->requestFromRawPayload(...)`.
- Framework adapter support via `WebhookRequestAdapterInterface` and
  `$client->webhooks()->requestFromAdapter(...)`.
- End-to-end processing with structured result via `$client->webhookProcessor(...)`.
- Better error boundaries and safer production behavior.
