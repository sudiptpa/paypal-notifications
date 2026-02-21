<?php

declare(strict_types=1);

namespace Sujip\PayPal\Notifications\Resource;

use Sujip\PayPal\Notifications\Config\ClientConfig;
use Sujip\PayPal\Notifications\Contracts\LoggerInterface;
use Sujip\PayPal\Notifications\Contracts\TransportInterface;
use Sujip\PayPal\Notifications\Exception\TransportException;
use Sujip\PayPal\Notifications\Http\HttpRequest;
use Sujip\PayPal\Notifications\InstantPaymentNotification\InstantPaymentNotificationStatus;
use Sujip\PayPal\Notifications\InstantPaymentNotification\VerifyInstantPaymentNotificationRequest;
use Sujip\PayPal\Notifications\InstantPaymentNotification\VerifyInstantPaymentNotificationResult;

final class InstantPaymentNotificationResource
{
    public function __construct(
        private readonly ClientConfig $config,
        private readonly TransportInterface $transport,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function verify(VerifyInstantPaymentNotificationRequest $request): VerifyInstantPaymentNotificationResult
    {
        $body = 'cmd=_notify-validate';

        $encoded = $request->toUrlEncodedBody();
        if ($encoded !== '') {
            $body .= '&'.$encoded;
        }

        try {
            $response = $this->transport->send(new HttpRequest(
                method: 'POST',
                url: $this->config->environment->ipnVerifyUrl(),
                headers: [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                    'Accept' => 'text/plain',
                ],
                body: $body,
                timeoutSeconds: $this->config->timeoutSeconds,
            ));
        } catch (TransportException $exception) {
            $this->logger->error('PayPal Instant Payment Notification verification transport error.');

            return new VerifyInstantPaymentNotificationResult(
                status: InstantPaymentNotificationStatus::ERROR,
                rawResponseBody: '',
                httpStatusCode: 0,
                errorMessage: $exception->getMessage(),
            );
        }

        $normalized = strtoupper(trim($response->body));

        if ($normalized === InstantPaymentNotificationStatus::VERIFIED->value) {
            return new VerifyInstantPaymentNotificationResult(
                status: InstantPaymentNotificationStatus::VERIFIED,
                rawResponseBody: $response->body,
                httpStatusCode: $response->statusCode,
            );
        }

        if ($normalized === InstantPaymentNotificationStatus::INVALID->value) {
            return new VerifyInstantPaymentNotificationResult(
                status: InstantPaymentNotificationStatus::INVALID,
                rawResponseBody: $response->body,
                httpStatusCode: $response->statusCode,
            );
        }

        return new VerifyInstantPaymentNotificationResult(
            status: InstantPaymentNotificationStatus::ERROR,
            rawResponseBody: $response->body,
            httpStatusCode: $response->statusCode,
            errorMessage: 'Unexpected PayPal Instant Payment Notification verification response.',
        );
    }

    public function verifyRaw(string $rawBody): VerifyInstantPaymentNotificationResult
    {
        return $this->verify(VerifyInstantPaymentNotificationRequest::fromRawBody($rawBody));
    }
}
