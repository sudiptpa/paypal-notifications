<?php

declare(strict_types=1);

use Sujip\PayPal\Notifications\Config\ClientConfig;
use Sujip\PayPal\Notifications\Config\Environment;
use Sujip\PayPal\Notifications\InstantPaymentNotification\InstantPaymentNotificationStatus;
use Sujip\PayPal\Notifications\PayPalClient;
use Sujip\PayPal\Notifications\Transport\CurlTransport;

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
$result = $client->ipn()->verifyRaw($rawBody);

if ($result->status === InstantPaymentNotificationStatus::VERIFIED) {
    http_response_code(200);
    echo 'VERIFIED';
    exit;
}

http_response_code(400);
echo $result->status->value;
