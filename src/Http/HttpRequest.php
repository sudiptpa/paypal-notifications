<?php

declare(strict_types=1);

namespace Sujip\PayPal\Notifications\Http;

final readonly class HttpRequest
{
    /**
     * @param array<string,string> $headers
     */
    public function __construct(
        public string $method,
        public string $url,
        public array $headers = [],
        public string $body = '',
        public int $timeoutSeconds = 10,
    ) {
    }
}
