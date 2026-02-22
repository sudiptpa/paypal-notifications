# Adding a New Event Mapping

This guide explains how to add a typed PayPal webhook event safely.

## 1) Create the event class

Add a class under `src/Webhook/Event/` extending `AbstractWebhookEvent`.

Example template:

```php
<?php

declare(strict_types=1);

namespace Sujip\PayPal\Notifications\Webhook\Event;

final class PaymentCaptureSomethingEvent extends AbstractWebhookEvent
{
    public function captureId(): string
    {
        return $this->resourceString('id');
    }
}
```

## 2) Map `event_type` in `EventFactory`

Add your event type -> class mapping in `EventFactory::fromPayload()`.

## 3) Add fixture coverage

Add a sanitized payload fixture in `tests/Fixtures/webhooks/` and include it in `tests/Fixtures/webhooks/fixtures.php`.

## 4) Add/Update tests

- Update `tests/Unit/EventFactoryTest.php` for direct factory mapping.
- Update `tests/Contract/WebhookEventFixturesContractTest.php` to lock fixture behavior.

## 5) Backward compatibility checks

- Keep existing event classes unchanged unless absolutely required.
- Keep unknown types mapped to `UnknownWebhookEvent`.

