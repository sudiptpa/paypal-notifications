<?php

declare(strict_types=1);

namespace Sujip\PayPal\Notifications\Log;

use Sujip\PayPal\Notifications\Contracts\LoggerInterface;

final class NullLogger implements LoggerInterface
{
    public function debug(string $message, array $context = []): void
    {
    }

    public function info(string $message, array $context = []): void
    {
    }

    public function warning(string $message, array $context = []): void
    {
    }

    public function error(string $message, array $context = []): void
    {
    }
}
