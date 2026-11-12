<?php

declare(strict_types=1);

namespace Darkterminal\TursoPlatformAPI\Client;

use Darkterminal\TursoPlatformAPI\Exceptions\ConfigurationException;
use Darkterminal\TursoPlatformAPI\Transport\CurlTransport;
use Psr\Http\Client\ClientInterface;

/**
 * Builder for the Turso Platform client.
 *
 * Example:
 *     $client = (new ClientBuilder())
 *         ->withToken($token)
 *         ->withOrganization('acme')
 *         ->build();
 */
final class ClientBuilder
{
    private ?string $token = null;
    private ?string $organization = null;
    private ?string $organizationId = null;
    private string $baseUrl = Configuration::DEFAULT_BASE_URL;
    private int $timeout = Configuration::DEFAULT_TIMEOUT;
    private ?ClientInterface $transport = null;

    public function withToken(string $token): self
    {
        $clone = clone $this;
        $clone->token = $token;
        return $clone;
    }

    public function withOrganization(string $organization): self
    {
        $clone = clone $this;
        $clone->organization = $organization;
        return $clone;
    }

    /**
     * Set the organization ID (UUID) for v3 recovery operations
     * (listDeletedDatabases, restoreDatabase, listDeletedGroups, restoreGroup).
     */
    public function withOrganizationId(string $organizationId): self
    {
        $clone = clone $this;
        $clone->organizationId = $organizationId;
        return $clone;
    }

    public function withBaseUrl(string $baseUrl): self
    {
        $clone = clone $this;
        $clone->baseUrl = rtrim($baseUrl, '/');
        return $clone;
    }

    public function withTimeout(int $seconds): self
    {
        if ($seconds < 1) {
            throw new ConfigurationException('Timeout must be at least 1 second.');
        }
        $clone = clone $this;
        $clone->timeout = $seconds;
        return $clone;
    }

    /** Inject any PSR-18 HTTP client (e.g. Guzzle, Curl client, test double). */
    public function withTransport(ClientInterface $transport): self
    {
        $clone = clone $this;
        $clone->transport = $transport;
        return $clone;
    }

    public function build(): Client
    {
        if ($this->token === null) {
            throw new ConfigurationException('An API token is required. Call withToken() before build().');
        }

        return new Client(new Configuration(
            token: $this->token,
            organization: $this->organization,
            organizationId: $this->organizationId,
            baseUrl: $this->baseUrl,
            timeout: $this->timeout,
            transport: $this->transport ?? new CurlTransport($this->timeout),
        ));
    }
}
