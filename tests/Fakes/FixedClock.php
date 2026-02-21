<?php

declare(strict_types=1);

namespace Sujip\PayPal\Notifications\Tests\Fakes;

use Sujip\PayPal\Notifications\Contracts\ClockInterface;

final class FixedClock implements ClockInterface
{
    private \DateTimeImmutable $now;

    public function __construct(\DateTimeImmutable $now)
    {
        $this->now = $now;
    }

    public function now(): \DateTimeImmutable
    {
        return $this->now;
    }

    public function forwardSeconds(int $seconds): void
    {
        $this->now = $this->now->modify(sprintf('+%d seconds', $seconds));
    }
}
