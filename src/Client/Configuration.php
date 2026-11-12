<?php

declare(strict_types=1);

namespace Darkterminal\TursoPlatformAPI\Client;

use Darkterminal\TursoPlatformAPI\Exceptions\ConfigurationException;
use Psr\Http\Client\ClientInterface;

/**
 * Immutable client configuration. Construct via ClientBuilder.
 *
 * The default base URL points at the Turso Platform API (https://api.turso.tech).
 * The default transport is the SDK's CurlTransport; inject any PSR-18 client
 * to control redirects, timeouts and credential handling.
 */
final class Configuration
{
    public const DEFAULT_BASE_URL = 'https://api.turso.tech';
    public const DEFAULT_TIMEOUT = 20;

    public function __construct(
        public readonly string $token,
        public readonly string $baseUrl = self::DEFAULT_BASE_URL,
        public readonly int $timeout = self::DEFAULT_TIMEOUT,
        public readonly ?ClientInterface $transport = null,
    ) {
        $token = trim($token);
        if ($token === '') {
            throw new ConfigurationException('Platform API token must not be empty.');
        }
        if ($timeout < 1) {
            throw new ConfigurationException('Timeout must be at least 1 second.');
        }
        $host = strtolower(parse_url($baseUrl, PHP_URL_HOST) ?: '');
        if ($host === '' || !in_array(parse_url($baseUrl, PHP_URL_SCHEME), ['http', 'https'], true)) {
            throw new ConfigurationException("Base URL must be an http(s) URL, got: $baseUrl");
        }
    }
}
