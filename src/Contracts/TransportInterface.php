<?php

declare(strict_types=1);

namespace Sujip\PayPal\Notifications\Contracts;

use Sujip\PayPal\Notifications\Http\HttpRequest;
use Sujip\PayPal\Notifications\Http\HttpResponse;

interface TransportInterface
{
    public function send(HttpRequest $request): HttpResponse;
}
