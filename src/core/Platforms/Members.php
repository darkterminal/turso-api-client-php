<?php

namespace Darkterminal\TursoPlatformAPI\core\Platforms;

use Darkterminal\TursoPlatformAPI\core\Enums\HttpResponse;
use Darkterminal\TursoPlatformAPI\core\Enums\RoleType;
use Darkterminal\TursoPlatformAPI\core\PlatformError;
use Darkterminal\TursoPlatformAPI\core\Response;
use Darkterminal\TursoPlatformAPI\core\Utils;

/**
 * Class Locations
 *
 * Represents a class for managing locations.
 */
final class Members implements Response
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
     * Locations constructor.
     *
     * @param string $token The API token used for authentication.
     */
    public function __construct(string $token, string $organizationName)
    {
        $this->token = $token;
        $this->organizationName = $organizationName;
    }

    /**
     * Get a list of members in an organization.
     *
     *
     * @return Members Returns an instance of Members for method chaining.
     */
    public function list(): Members
    {
        $endpoint = Utils::useAPI('members', 'members');
        $url = \str_replace('{organizationName}', $this->organizationName, $endpoint['url']);
        $response = Utils::makeRequest($endpoint['method'], $url, $this->token);

        if ($response['code'] !== HttpResponse::OK->value) {
            if (php_sapi_name() === 'cli') {
                throw new PlatformError($response['body']['error'], HttpResponse::tryFrom($response['code'])->statusMessage());
            } else {
                $this->response['list_members'] = [
                    'code' => $response['code'],
                    'error' => $response['body']['error'],
                ];
                return $this;
            }
        }

        $this->response['list_members'] = [
            'code' => $response['code'],
            'data' => $response['body']['members'],
        ];

        return $this;
    }

    /**
     * Add a member to the organization.
     *
     * @param string $username The username of the member.
     * @param RoleType $role The role of the member.
     *
     * @return Members Returns an instance of Members for method chaining or throws an Exception on failure.
     */
    public function addMember(string $username, RoleType $role = RoleType::MEMBER): Members
    {
        $endpoint = Utils::useAPI('members', 'add_member');
        $url = \str_replace('{organizationName}', $this->organizationName, $endpoint['url']);
        Utils::validateMemberRole($role->value);
        $response = Utils::makeRequest($endpoint['method'], $url, $this->token, [
            'username' => $username,
            'role' => $role->value,
        ]);

        if ($response['code'] !== HttpResponse::OK->value) {
            if (php_sapi_name() === 'cli') {
                throw new PlatformError($response['body']['error'], HttpResponse::tryFrom($response['code'])->statusMessage());
            } else {
                $this->response['add_member'] = [
                    'code' => $response['code'],
                    'error' => $response['body']['error'],
                ];
                return $this;
            }
        }

        $this->response['add_member'] = [
            'code' => $response['code'],
            'data' => $response['body']
        ];

        return $this;
    }

    /**
     * Remove a member from the organization.
     *
     * @param string $username The username of the member to be removed.
     *
     * @return Members Returns an instance of Members for method chaining.
     */
    public function removeMember(string $username): Members
    {
        $endpoint = Utils::useAPI('members', 'remove_member');
        $url = \str_replace('{organizationName}', $this->organizationName, $endpoint['url']);
        $url = \str_replace('{username}', $username, $endpoint['url']);
        $response = Utils::makeRequest($endpoint['method'], $url, $this->token);
        var_dump($response);

        if ($response['code'] !== HttpResponse::OK->value) {
            if (php_sapi_name() === 'cli') {
                throw new PlatformError($response['body']['error'], HttpResponse::tryFrom($response['code'])->statusMessage());
            } else {
                $this->response['remove_member'] = [
                    'code' => $response['code'],
                    'error' => $response['body']['error'],
                ];
                return $this;
            }
        }

        $this->response['remove_member'] = [
            'code' => $response['code'],
            'data' => $response['body']['member']
        ];

        return $this;
    }

    /**
     * Returns the result of the previous operation.
     *
     * @return array The result of the previous operation
     */
    private function results(): array
    {
        return match (true) {
            isset($this->response['list_members']) => $this->response['list_members'],
            isset($this->response['add_member']) => $this->response['add_member'],
            isset($this->response['remove_member']) => $this->response['remove_member'],
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
