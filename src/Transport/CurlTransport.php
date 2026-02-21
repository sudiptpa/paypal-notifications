<?php

declare(strict_types=1);

namespace Sujip\PayPal\Notifications\Transport;

use Sujip\PayPal\Notifications\Contracts\TransportInterface;
use Sujip\PayPal\Notifications\Exception\TransportException;
use Sujip\PayPal\Notifications\Http\HttpRequest;
use Sujip\PayPal\Notifications\Http\HttpResponse;

final class CurlTransport implements TransportInterface
{
    public function send(HttpRequest $request): HttpResponse
    {
        $ch = curl_init();
        if (!$ch instanceof \CurlHandle) {
            throw new TransportException('Unable to initialize cURL transport.');
        }

        $responseHeaders = [];
        $headerLines = [];

        foreach ($request->headers as $name => $value) {
            $headerLines[] = $name.': '.$value;
        }

        curl_setopt_array($ch, [
            CURLOPT_URL => $request->url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => false,
            CURLOPT_HTTPHEADER => $headerLines,
            CURLOPT_TIMEOUT => $request->timeoutSeconds,
            CURLOPT_CONNECTTIMEOUT => $request->timeoutSeconds,
            CURLOPT_CUSTOMREQUEST => strtoupper($request->method),
            CURLOPT_POSTFIELDS => $request->body,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_HEADERFUNCTION => static function ($curl, string $line) use (&$responseHeaders): int {
                $trimmed = trim($line);
                if ($trimmed === '' || !str_contains($trimmed, ':')) {
                    return strlen($line);
                }

                [$name, $value] = explode(':', $trimmed, 2);
                $responseHeaders[trim($name)] = trim($value);

                return strlen($line);
            },
        ]);

        $body = curl_exec($ch);

        if ($body === false) {
            $error = curl_error($ch);
            $this->closeHandle($ch);
            throw new TransportException('cURL request failed: '.$error);
        }

        $statusCode = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $this->closeHandle($ch);

        return new HttpResponse($statusCode, (string) $body, $responseHeaders);
    }

    /**
     * cURL handles are closed automatically in modern PHP. Avoid calling curl_close()
     * on PHP 8.5+ where the function is deprecated.
     */
    private function closeHandle(\CurlHandle $handle): void
    {
        if (\PHP_VERSION_ID < 80500) {
            curl_close($handle);
            return;
        }

        unset($handle);
    }
}
