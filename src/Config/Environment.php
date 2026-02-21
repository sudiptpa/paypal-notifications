<?php

declare(strict_types=1);

namespace Sujip\PayPal\Notifications\Config;

enum Environment: string
{
    case Sandbox = 'sandbox';
    case Live = 'live';

    public function apiBaseUrl(): string
    {
        return match ($this) {
            self::Sandbox => 'https://api-m.sandbox.paypal.com',
            self::Live => 'https://api-m.paypal.com',
        };
    }

    public function ipnVerifyUrl(): string
    {
        return match ($this) {
            self::Sandbox => 'https://ipnpb.sandbox.paypal.com/cgi-bin/webscr',
            self::Live => 'https://ipnpb.paypal.com/cgi-bin/webscr',
        };
    }
}
