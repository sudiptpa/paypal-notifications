<?php

declare(strict_types=1);

namespace Sujip\PayPal\Notifications\Webhook;

use Sujip\PayPal\Notifications\Config\ClientConfig;
use Sujip\PayPal\Notifications\Exception\VerificationException;

final class WebhookCertUrlPolicy
{
    public function assertValid(string $certUrl, ClientConfig $config): void
    {
        $parts = parse_url($certUrl);
        if (!is_array($parts)) {
            throw new VerificationException('cert_url is not a valid URL.');
        }

        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = strtolower((string) ($parts['host'] ?? ''));
        $path = (string) ($parts['path'] ?? '');
        $port = $parts['port'] ?? null;
        $query = (string) ($parts['query'] ?? '');
        $fragment = (string) ($parts['fragment'] ?? '');
        $user = (string) ($parts['user'] ?? '');
        $pass = (string) ($parts['pass'] ?? '');

        if ($scheme !== 'https') {
            throw new VerificationException('cert_url must use https.');
        }

        if ($host === '') {
            throw new VerificationException('cert_url must include a host.');
        }

        if ($query !== '' || $fragment !== '') {
            throw new VerificationException('cert_url must not include query or fragment.');
        }

        if ($user !== '' || $pass !== '') {
            throw new VerificationException('cert_url must not include user info.');
        }

        if ($config->requireDefaultHttpsPortForWebhookCertUrl && $port !== null && (int) $port !== 443) {
            throw new VerificationException('cert_url must use default HTTPS port.');
        }

        $trusted = false;
        foreach ($config->trustedWebhookCertHostSuffixes as $suffix) {
            $normalizedSuffix = ltrim(strtolower($suffix), '.');
            if ($normalizedSuffix === '') {
                continue;
            }

            if ($host === $normalizedSuffix || str_ends_with($host, '.'.$normalizedSuffix)) {
                $trusted = true;
                break;
            }
        }

        if (!$trusted) {
            throw new VerificationException('cert_url host is not in trusted PayPal host suffixes.');
        }

        if (!str_starts_with($path, $config->requiredWebhookCertPathPrefix)) {
            throw new VerificationException('cert_url path does not match required certificate path prefix.');
        }
    }
}
