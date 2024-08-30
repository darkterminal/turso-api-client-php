<?php

namespace Darkterminal\TursoPlatformAPI\core\Platforms;

use Darkterminal\TursoPlatformAPI\core\Enums\HttpResponse;
use Darkterminal\TursoPlatformAPI\core\PlatformError;
use Darkterminal\TursoPlatformAPI\core\Response;
use Darkterminal\TursoPlatformAPI\core\Utils;

/**
 * Class AuditLogs
 *
 * Represents a class for retrieving audit logs.
 */
final class AuditLogs implements Response
{
    /**
     * @var string The API token used for authentication.
     */
    protected string $token;

    /**
     * @var string The name of the organization.
     */
    protected string $organizationName;

    /**
     * @var mixed The response from the API request.
     */
    protected $response;

    /**
     * AuditLogs constructor.
     *
     * @param string $organizationName The name of the organization.
     * @param string $token The API token used for authentication.
     */
    public function __construct(string $token, string $organizationName)
    {
        $this->token = $token;
        $this->organizationName = $organizationName;
    }

    /**
     * List audit logs for a specific organization.
     *
     * @param int $page_size The number of items per page.
     * @param int $page The page number.
     *
     * @return AuditLogs Returns an instance of AuditLogs for method chaining.
     */
    public function list(int $page_size = 10, int $page = 1): AuditLogs
    {
        $endpoint = Utils::useAPI('audit_logs', 'list');
        $url = \str_replace('{organizationName}', $this->organizationName, $endpoint['url']);
        $url = $url . "?" . \http_build_query([
            'page_size' => $page_size,
            'page' => $page
        ]);
        $response = Utils::makeRequest($endpoint['method'], $url, $this->token);

        if ($response['code'] !== HttpResponse::OK->value) {
            if (php_sapi_name() === 'cli') {
                throw new PlatformError($response['body']['error'], 'LIST_AUDIT_LOGS_FAILED');
            } else {
                $this->response['list_audit_logs'] = [
                    'code' => HttpResponse::NOT_FOUND,
                    'error' => $response['body']['error']
                ];
            }
        }
        $this->response['list_audit_logs'] = [
            'code' => HttpResponse::OK,
            'logs' => $response['audit_logs']
        ];

        return $this;
    }

    private function results(): array|string
    {
        return match (true) {
            isset($this->response['list_audit_logs']) => $this->response['list_audit_logs'],
            default => $this->response,
        };
    }

    /**
     * Get the API response as an array.
     *
     * @return array The API response as an array.
     */
    public function get(): array
    {
        return $this->results();
    }

    /**
     * Get the API response as a JSON string or array.
     *
     * @param bool $pretty Whether to use pretty formatting.
     * @return string|array The API response as a JSON string, array, or null if not applicable.
     */
    public function toJSON(bool $pretty = false): string|array
    {
        return $pretty ? json_encode($this->results(), JSON_PRETTY_PRINT) : json_encode($this->results());
    }
}
