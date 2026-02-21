<?php

declare(strict_types=1);

namespace Sujip\PayPal\Notifications\Config;

use Sujip\PayPal\Notifications\Contracts\ClockInterface;

final class SystemClock implements ClockInterface
{
    public function now(): \DateTimeImmutable
    {
        return new \DateTimeImmutable('now');
    }
}
