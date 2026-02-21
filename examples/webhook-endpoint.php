<?php

declare(strict_types=1);

use Sujip\PayPal\Notifications\Config\ClientConfig;
use Sujip\PayPal\Notifications\Config\Environment;
use Sujip\PayPal\Notifications\PayPalClient;
use Sujip\PayPal\Notifications\Transport\CurlTransport;
use Sujip\PayPal\Notifications\Webhook\Event\WebhookEventType;
use Sujip\PayPal\Notifications\Webhook\WebhookEventRouter;

require __DIR__.'/../vendor/autoload.php';

$client = new PayPalClient(
    config: new ClientConfig(
        clientId: $_ENV['PAYPAL_CLIENT_ID'] ?? '',
        clientSecret: $_ENV['PAYPAL_CLIENT_SECRET'] ?? '',
        webhookId: $_ENV['PAYPAL_WEBHOOK_ID'] ?? '',
        environment: ($_ENV['PAYPAL_ENV'] ?? 'sandbox') === 'live'
            ? Environment::Live
            : Environment::Sandbox,
    ),
    transport: new CurlTransport(),
);

$rawBody = file_get_contents('php://input') ?: '';
$headers = function_exists('getallheaders') ? getallheaders() : $_SERVER;

try {
    $request = $client->webhooks()->requestFromRawPayload($rawBody, $headers);
    $verification = $client->webhooks()->verifySignature($request);

    if (!$verification->isSuccess()) {
        http_response_code(400);
        echo 'Invalid PayPal signature';
        exit;
    }

    $event = $client->webhooks()->parseRawEvent($rawBody);

    $router = (new WebhookEventRouter())
        ->onCaptureCompleted(static function ($event): void {
            // Mark order paid in your system.
        })
        ->onCaptureRefunded(static function ($event): void {
            // Mark order refunded in your system.
        })
        ->onType(WebhookEventType::CustomerDisputeCreated, static function ($event): void {
            // Escalate dispute flow.
        })
        ->fallback(static function ($event): void {
            // Log unknown events for visibility.
        });

    $router->dispatch($event);

    http_response_code(200);
    echo 'OK';
} catch (Throwable) {
    http_response_code(400);
    echo 'Webhook processing failed';
}
