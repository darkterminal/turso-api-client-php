<?php

namespace Darkterminal\TursoPlatformAPI\core\Platforms;

use Darkterminal\TursoPlatformAPI\core\Enums\HttpResponse;
use Darkterminal\TursoPlatformAPI\core\Enums\InvoiceType;
use Darkterminal\TursoPlatformAPI\core\PlatformError;
use Darkterminal\TursoPlatformAPI\core\Response;
use Darkterminal\TursoPlatformAPI\core\Utils;

/**
 * Class Organizations
 *
 * Represents a class for managing organizations.
 */
final class Organizations implements Response
{
    /**
     * @var string The API token used for authentication.
     */
    protected string $token;

    /**
     * @var mixed The response from the API request.
     */
    protected $response;

    /**
     * Organizations constructor.
     *
     * @param string $token The API token used for authentication.
     */
    public function __construct(string $token)
    {
        $this->token = $token;
    }

    /**
     * Get a list of organizations.
     *
     * @return Organizations Returns an instance of Organizations for method chaining.
     */
    public function list(): Organizations
    {
        $endpoint = Utils::useAPI('organizations', 'list');
        $response = Utils::makeRequest($endpoint['method'], $endpoint['url'], $this->token);

        if ($response['code'] !== HttpResponse::OK->value) {
            if (php_sapi_name() === 'cli') {
                throw new PlatformError($response['body']['error'], HttpResponse::tryFrom($response['code'])->statusMessage());
            } else {
                $this->response['invalidated_token_group'] = [
                    'code' => $response['code'],
                    'error' => $response['body']['error']
                ];
            }
        }

        $this->response['list_organizations'] = [
            'code' => $response['code'],
            'data' => $response['body']
        ];
        
        return $this;
    }

    /**
     * Update organization details.
     *
     * @param string $organizationName The name of the organization.
     * @param bool $overages Optional. Whether overages are allowed (default: true).
     *
     * @return Organizations Returns an instance of Organizations for method chaining.
     */
    public function update(string $organizationName, bool $overages = true): Organizations
    {
        $endpoint = Utils::useAPI('organizations', 'update');
        $url = \str_replace('{organizationName}', $organizationName, $endpoint['url']);
        $response = Utils::makeRequest($endpoint['method'], $url, $this->token, ['overages' => $overages]);

        if ($response['code'] !== HttpResponse::OK->value) {
            if (php_sapi_name() === 'cli') {
                throw new PlatformError($response['body']['error'], HttpResponse::tryFrom($response['code'])->statusMessage());
            } else {
                $this->response['update_organization'] = [
                    'code' => $response['code'],
                    'body' => $response['body']['error']
                ];
            }
        }

        $this->response['update_organization'] = [
            'code' => $response['code'],
            'data' => $response['body']['organization']
        ];

        return $this;
    }

    /**
     * Returns a list of available plans and their quotas.
     *
     * @param string $organizationName The name of the organization.
     *
     * @return Organizations Returns an instance of Organizations for method chaining.
     */
    public function plans(string $organizationName): Organizations
    {
        $endpoint = Utils::useAPI('organizations', 'plans');
        $url = \str_replace('{organizationName}', $organizationName, $endpoint['url']);
        $response = Utils::makeRequest($endpoint['method'], $url, $this->token);

        if ($response['code'] !== HttpResponse::OK->value) {
            if (php_sapi_name() === 'cli') {
                throw new PlatformError($response['body']['error'], HttpResponse::tryFrom($response['code'])->statusMessage());
            } else {
                $this->response['plans_organization'] = [
                    'code' => $response['code'],
                    'body' => $response['body']['error']
                ];
            }
        }

        $this->response['plans_organization'] = [
            'code' => $response['code'],
            'data' => $response['body']['plans']
        ];

        return $this;
    }

    /**
     * Get the subscription details for an organization.
     *
     * @param string $organizationName The name of the organization.
     *
     * @return Organizations Returns an instance of Organizations for method chaining.
     */
    public function subscription(string $organizationName): Organizations
    {
        $endpoint = Utils::useAPI('organizations', 'subscription');
        $url = \str_replace('{organizationName}', $organizationName, $endpoint['url']);
        $response = Utils::makeRequest($endpoint['method'], $url, $this->token);

        if ($response['code'] !== HttpResponse::OK->value) {
            if (php_sapi_name() === 'cli') {
                throw new PlatformError($response['body']['error'], HttpResponse::tryFrom($response['code'])->statusMessage());
            } else {
                $this->response['subscription_organization'] = [
                    'code' => $response['code'],
                    'body' => $response['body']['error']
                ];
            }
        }

        $this->response['subscription_organization'] = [
            'code' => $response['code'],
            'data' => $response['body']['subscription']
        ];

        return $this;
    }

    /**
     * Get a list of invoices for an organization.
     *
     * @param string $organizationName The name of the organization.
     * @param InvoiceType $invoiceType The type of invoices to retrieve. Defaults to InvoiceType::ALL.
     *
     * @return Organizations Returns an instance of Organizations for method chaining.
     */
    public function invoices(string $organizationName, InvoiceType $invoiceType = InvoiceType::ALL): Organizations
    {
        $endpoint = Utils::useAPI('organizations', 'invoices');
        $url = \str_replace('{organizationName}', $organizationName, $endpoint['url']);
        $url = "?type={$invoiceType->value}";
        $response = Utils::makeRequest($endpoint['method'], $url, $this->token);

        if ($response['code'] !== HttpResponse::OK->value) {
            if (php_sapi_name() === 'cli') {
                throw new PlatformError($response['body']['error'] ?? 'Nothing', HttpResponse::tryFrom($response['code'])->statusMessage());
            } else {
                $this->response['invoices_organization'] = [
                    'code' => $response['code'],
                    'body' => $response['body']['error']
                ];
            }
        }

        $this->response['invoices_organization'] = [
            'code' => $response['code'],
            'data' => $response['body']['invoices']
        ];

        return $this;
    }

    /**
     * Get the current usage for an organization.
     *
     * @param string $organizationName The name of the organization.
     *
     * @return Organizations Returns an instance of Organizations for method chaining.
     */
    public function currentUsage(string $organizationName): Organizations
    {
        $endpoint = Utils::useAPI('organizations', 'current_usage');
        $url = \str_replace('{organizationName}', $organizationName, $endpoint['url']);
        $response = Utils::makeRequest($endpoint['method'], $url, $this->token);

        if ($response['code'] !== HttpResponse::OK->value) {
            if (php_sapi_name() === 'cli') {
                throw new PlatformError($response['body']['error'], HttpResponse::tryFrom($response['code'])->statusMessage());
            } else {
                $this->response['current_usage_organization'] = [
                    'code' => $response['code'],
                    'body' => $response['body']['error']
                ];
            }
        }

        $this->response['current_usage_organization'] = [
            'code' => $response['code'],
            'data' => $response['body']['organization']
        ];

        return $this;
    }

    /**
     * Returns the result of the previous operation.
     *
     * @return array|string The result of the previous operation
     */
    private function results(): array|string
    {
        return match (true) {
            isset($this->response['list_organizations']) => $this->response['list_organizations'],
            isset($this->response['update_organization']) => $this->response['update_organization'],
            isset($this->response['plans_organization']) => $this->response['plans_organization'],
            isset($this->response['subscription_organization']) => $this->response['subscription_organization'],
            isset($this->response['invoices_organization']) => $this->response['invoices_organization'],
            isset($this->response['current_usage_organization']) => $this->response['current_usage_organization'],
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
