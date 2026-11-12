<?php

declare(strict_types=1);

namespace Darkterminal\TursoPlatformAPI\Resources;

use Darkterminal\TursoPlatformAPI\Client\Client;
use Darkterminal\TursoPlatformAPI\Exceptions\ValidationException;

/**
 * Audit logs resource — operationId: listOrganizationAuditLogs.
 *
 * Envelope: {audit_logs: [AuditLog], pagination: {page, page_size, ...}}
 */
final class AuditLogs
{
    public function __construct(
        private readonly Client $client,
    ) {}

    /**
     * List audit logs.
     *
     * @return array{audit_logs: array<mixed>, pagination: array<string, int|null>}
     */
    public function list(int $page = 1, int $pageSize = 25): array
    {
        if ($page < 1) {
            throw new ValidationException('Page must be at least 1.');
        }
        if ($pageSize < 1 || $pageSize > 100) {
            throw new ValidationException('Page size must be between 1 and 100.');
        }

        /** @var array<mixed> $decoded */
        $decoded = $this->client->request('GET',
            $this->orgPath('/audit-logs'),
            ['page' => $page, 'page_size' => $pageSize]);
        return $decoded;
    }

    private function orgPath(string $segment = ''): string
    {
        $org = $this->client->organization();
        $base = '/v1/organizations/' . rawurlencode($org);
        return $segment === '' ? $base : $base . $segment;
    }
}
