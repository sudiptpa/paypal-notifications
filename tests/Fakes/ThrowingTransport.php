<?php

declare(strict_types=1);

namespace Sujip\PayPal\Notifications\Tests\Fakes;

use Sujip\PayPal\Notifications\Contracts\TransportInterface;
use Sujip\PayPal\Notifications\Exception\TransportException;
use Sujip\PayPal\Notifications\Http\HttpRequest;
use Sujip\PayPal\Notifications\Http\HttpResponse;

final class ThrowingTransport implements TransportInterface
{
    public function __construct(private readonly string $message = 'transport failed')
    {
    }

    public function send(HttpRequest $request): HttpResponse
    {
        throw new TransportException($this->message);
    }
}

