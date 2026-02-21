<?php

declare(strict_types=1);

namespace Sujip\PayPal\Notifications\Http;

final readonly class HttpResponse
{
    /**
     * @param array<string,string> $headers
     */
    public function __construct(
        public int $statusCode,
        public string $body,
        public array $headers = [],
    ) {
    }

    public function header(string $name): ?string
    {
        $normalized = strtolower($name);

        foreach ($this->headers as $headerName => $value) {
            if (strtolower($headerName) === $normalized) {
                return $value;
            }
        }

        return null;
    }
}
