# Changelog

All notable changes to this project will be documented in this file.

## [Unreleased]

### Added

- PHP baseline standardized to 8.2+ with support through 8.5.
- Phase 1 typed webhook event envelope support:
  - `WebhookEventInterface`
  - `UnknownWebhookEvent`
  - `EventFactory`
  - `WebhooksResource::parseEvent(...)`
  - `WebhooksResource::parseRawEvent(...)`
- Tests for event envelope parsing and invalid raw-event payload handling.

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
- GitHub Actions CI matrix (PHP 8.1-8.5) with lowest-dependency run
- Strict webhook `cert_url` validation (`https` + PayPal domain)
- Explicit non-2xx webhook verification failure handling
- OAuth transport-failure mapping to authentication exceptions
- PHP 8.5-safe cURL handle close behavior
- Production-focused README with simple-to-advanced usage
