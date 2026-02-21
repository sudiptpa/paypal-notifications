<?php

declare(strict_types=1);

namespace Sujip\PayPal\Notifications\InstantPaymentNotification;

enum InstantPaymentNotificationStatus: string
{
    case VERIFIED = 'VERIFIED';
    case INVALID = 'INVALID';
    case ERROR = 'ERROR';
}
