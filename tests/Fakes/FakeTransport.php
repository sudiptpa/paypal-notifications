<?php

declare(strict_types=1);

namespace Sujip\PayPal\Notifications\Tests\Fakes;

use Sujip\PayPal\Notifications\Contracts\TransportInterface;
use Sujip\PayPal\Notifications\Http\HttpRequest;
use Sujip\PayPal\Notifications\Http\HttpResponse;

final class FakeTransport implements TransportInterface
{
    /** @var list<HttpResponse> */
    private array $queue;

    /** @var list<HttpRequest> */
    public array $requests = [];

    /** @param list<HttpResponse> $responses */
    public function __construct(array $responses)
    {
        $this->queue = $responses;
    }

    public function send(HttpRequest $request): HttpResponse
    {
        $this->requests[] = $request;

        if ($this->queue === []) {
            throw new \RuntimeException('FakeTransport queue is empty.');
        }

        return array_shift($this->queue);
    }
}
