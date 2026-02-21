<?php

declare(strict_types=1);

use Sujip\PayPal\Notifications\Config\ClientConfig;
use Sujip\PayPal\Notifications\Config\Environment;
use Sujip\PayPal\Notifications\Contracts\TransportInterface;
use Sujip\PayPal\Notifications\Http\HttpRequest;
use Sujip\PayPal\Notifications\Http\HttpResponse;
use Sujip\PayPal\Notifications\PayPalClient;

require __DIR__.'/../vendor/autoload.php';

final class DemoTransport implements TransportInterface
{
    public function send(HttpRequest $request): HttpResponse
    {
        // Map your preferred HTTP client response into HttpResponse.
        return new HttpResponse(statusCode: 200, body: '{}', headers: []);
    }
}

$client = new PayPalClient(
    config: new ClientConfig(
        clientId: 'client-id',
        clientSecret: 'client-secret',
        webhookId: 'webhook-id',
        environment: Environment::Sandbox,
    ),
    transport: new DemoTransport(),
);
