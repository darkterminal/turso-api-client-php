<?php

declare(strict_types=1);

namespace Darkterminal\TursoPlatformAPI\Resources;

use Darkterminal\TursoPlatformAPI\Client\Client;
use Darkterminal\TursoPlatformAPI\Exceptions\ValidationException;

/**
 * Organizations resource — 7 operations pinned in openapi/coverage-manifest.yaml
 * (operationIds: listOrganizations, getOrganization, updateOrganization,
 * listOrganizationPlans, listOrganizationInvoices, getOrganizationSubscription,
 * getOrganizationUsage).
 *
 * Envelopes:
 *   listOrganizations -> bare array [Organization]
 *   get/update/usage -> {organization: {...}}
 *   plans -> {plans: [...]}
 *   invoices -> {invoices: [...]}  (filter via query `type`)
 *   subscription -> {subscription: {...}}
 */
final class Organizations
{
    /** @var list<string> */
    private const ALLOWED_UPDATE_KEYS = ['overages', 'require_mfa', 'restore_enabled'];

    public function __construct(
        private readonly Client $client,
    ) {}

    /**
     * List organizations visible to the token (bare array).
     * @return array<mixed>
     */
    public function list(): array
    {
        /** @var array<mixed> $decoded */
        $decoded = $this->client->request('GET', '/v1/organizations');
        return $decoded;
    }

    /**
     * Get an organization.
     * @return array<mixed>
     */
    public function get(): array
    {
        $decoded = $this->client->request('GET', $this->orgPath());
        return $decoded['organization'] ?? $decoded;
    }

    /**
     * Update an organization.
     *
     * Allowed keys only (unknown keys throw ValidationException):
     * overages, require_mfa, restore_enabled. All booleans.
     * (restore_enabled must be updated separately from other settings.)
     *
     * @param array<string, bool> $settings
     * @return array<mixed>
     */
    public function update(array $settings): array
    {
        foreach (array_keys($settings) as $key) {
            if (!in_array($key, self::ALLOWED_UPDATE_KEYS, true)) {
                throw new ValidationException('Unsupported organization setting: ' . $key . '. Allowed: ' . implode(', ', self::ALLOWED_UPDATE_KEYS) . '.');
            }
            if (!is_bool($settings[$key])) {
                throw new ValidationException("{$key} must be a boolean.");
            }
        }

        $decoded = $this->client->request('PATCH', $this->orgPath(), [], $settings);
        return $decoded['organization'] ?? $decoded;
    }

    /**
     * List plans available for the organization.
     * @return array<mixed>
     */
    public function listPlans(): array
    {
        $decoded = $this->client->request('GET', $this->orgPath('/plans'));
        return $decoded['plans'] ?? $decoded;
    }

    /**
     * List invoices.
     *
     * @param 'all'|'upcoming'|'issued' $type Invoice type filter.
     * @return array<mixed>
     */
    public function listInvoices(string $type = 'all'): array
    {
        if (!in_array($type, ['all', 'upcoming', 'issued'], true)) {
            throw new ValidationException('Invoice type must be one of: all, upcoming, issued.');
        }

        $decoded = $this->client->request('GET', $this->orgPath('/invoices'), ['type' => $type]);
        return $decoded['invoices'] ?? $decoded;
    }

    /**
     * Get the current subscription.
     * @return array<mixed>
     */
    public function getSubscription(): array
    {
        $decoded = $this->client->request('GET', $this->orgPath('/subscription'));
        return $decoded['subscription'] ?? $decoded;
    }

    /**
     * Get usage for the organization.
     * @return array<mixed>
     */
    public function getUsage(): array
    {
        $decoded = $this->client->request('GET', $this->orgPath('/usage'));
        return $decoded['organization'] ?? $decoded;
    }

    /** Build the organization path. Segment must start with '/'. */
    private function orgPath(string $segment = ''): string
    {
        $org = $this->client->organization();
        $base = '/v1/organizations/' . rawurlencode($org);
        return $segment === '' ? $base : $base . $segment;
    }
}
