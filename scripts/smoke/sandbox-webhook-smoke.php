<?php

declare(strict_types=1);

/**
 * Manual sandbox smoke test helper.
 *
 * Usage:
 * 1) Start your local webhook endpoint.
 * 2) Configure PayPal sandbox webhook to hit that endpoint.
 * 3) Trigger a sandbox event from PayPal dashboard.
 * 4) Use this script to inspect your endpoint response quickly.
 *
 * Example:
 * php scripts/smoke/sandbox-webhook-smoke.php \
 *   --url="http://127.0.0.1:8000/webhook/paypal" \
 *   --payload='{"id":"WH-TEST","event_type":"PAYMENT.CAPTURE.COMPLETED","resource":{"id":"CAP-1"}}' \
 *   --header="PAYPAL-TRANSMISSION-ID: trans-1" \
 *   --header="PAYPAL-TRANSMISSION-TIME: 2026-02-21T00:00:00Z" \
 *   --header="PAYPAL-TRANSMISSION-SIG: sig" \
 *   --header="PAYPAL-CERT-URL: https://api-m.sandbox.paypal.com/v1/notifications/certs/CERT-123" \
 *   --header="PAYPAL-AUTH-ALGO: SHA256withRSA"
 */

$args = getopt('', ['url:', 'payload:', 'header::']);

$url = $args['url'] ?? null;
$payload = $args['payload'] ?? null;
$headers = $args['header'] ?? [];

if (!is_string($url) || $url === '' || !is_string($payload)) {
    fwrite(STDERR, "Missing required --url and --payload options.\n");
    exit(1);
}

if (!is_array($headers)) {
    $headers = [$headers];
}

$ch = curl_init($url);
if ($ch === false) {
    fwrite(STDERR, "Unable to initialize cURL.\n");
    exit(1);
}

curl_setopt_array($ch, [
    CURLOPT_CUSTOMREQUEST => 'POST',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POSTFIELDS => $payload,
    CURLOPT_HTTPHEADER => array_merge(['Content-Type: application/json'], $headers),
    CURLOPT_HEADER => true,
    CURLOPT_TIMEOUT => 15,
]);

$response = curl_exec($ch);
if ($response === false) {
    fwrite(STDERR, 'cURL error: '.curl_error($ch)."\n");
    curl_close($ch);
    exit(1);
}

$status = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
$headerSize = (int) curl_getinfo($ch, CURLINFO_HEADER_SIZE);
$responseHeaders = substr($response, 0, $headerSize);
$responseBody = substr($response, $headerSize);
curl_close($ch);

echo "HTTP Status: ".$status.PHP_EOL;
echo "Response Headers:".PHP_EOL.$responseHeaders.PHP_EOL;
echo "Response Body:".PHP_EOL.$responseBody.PHP_EOL;
