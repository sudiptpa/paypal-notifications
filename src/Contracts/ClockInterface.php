<?php

declare(strict_types=1);

namespace Sujip\PayPal\Notifications\Contracts;

interface ClockInterface
{
    public function now(): \DateTimeImmutable;
}
