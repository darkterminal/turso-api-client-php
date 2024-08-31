<?php
declare(strict_types=1);

namespace Darkterminal\TursoPlatformAPI\core\Platforms;

use Darkterminal\TursoPlatformAPI\core\Enums\Authorization;
use Darkterminal\TursoPlatformAPI\core\Enums\Extension;
use Darkterminal\TursoPlatformAPI\core\Enums\HttpResponse;
use Darkterminal\TursoPlatformAPI\core\Enums\Location;
use Darkterminal\TursoPlatformAPI\core\PlatformError;
use Darkterminal\TursoPlatformAPI\core\Response;
use Darkterminal\TursoPlatformAPI\core\Utils;

/**
 * Class Groups
 *
 * Represents a class for managing groups.
 */
final class Groups implements Response
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
     * Groups constructor.
     * 
     * @param string $token The API token used for authentication.
     * @param string $organizationName The name of the organization.
     */
    public function __construct(string $token, string $organizationName)
    {
        $this->token = $token;
        $this->organizationName = $organizationName;
    }

    /**
     * List groups for a specific organization.
     * 
     * @return Groups Returns an instance of Groups for method chaining.
     */
    public function list(): Groups
    {
        $endpoint = Utils::useAPI('groups', 'list');
        $url = \str_replace('{organizationName}', $this->organizationName, $endpoint['url']);
        $response = Utils::makeRequest($endpoint['method'], $url, $this->token);

        if ($response['code'] !== HttpResponse::OK->value) {
            if (php_sapi_name() === 'cli') {
                throw new PlatformError($response['body']['error'], HttpResponse::tryFrom($response['code'])->statusMessage());
            } else {
                $this->response['list_groups'] = [
                    'code' => $response['code'],
                    'error' => $response['body']['error']
                ];
            }
        }
        $this->response['list_groups'] = [
            'code' => $response['code'],
            'groups' => $response['body']['groups']
        ];

        return $this;
    }

    /**
     * Create a new group.
     *
     * @param string $groupName The name of the new group.
     * @param Location $location Optional. The location of the group (default: 'default').
     * @param Extension|array<Extension> $extensions Optional. The extensions to enable for new databases created in this group. 
     *                                               Users looking to enable vector extensions should instead use the native [libSQL vector datatype](https://docs.turso.tech/features/ai-and-embeddings).
     *
     * @return Groups Returns an instance of Groups for method chaining.
     */
    public function create(
        string $groupName,
        Location $location = Location::DEFAULT ,
        Extension|array $extensions = Extension::ALL
    ): Groups {
        $endpoint = Utils::useAPI('groups', 'create');
        $url = \str_replace('{organizationName}', $this->organizationName, $endpoint['url']);

        if ($location->value === 'default') {
            $closestRegion = Utils::closestRegion($this->token);
            $location = $closestRegion['body']['server'];
        }

        $body = [
            'name' => $groupName,
            'location' => $location,
            'extensions' => is_array($extensions) ? array_map(fn($extension) => $extension->value, $extensions) : $extensions->value
        ];

        $response = Utils::makeRequest($endpoint['method'], $url, $this->token, $body);

        if ($response['code'] !== HttpResponse::OK->value) {
            if (php_sapi_name() === 'cli') {
                throw new PlatformError($response['body']['error'], HttpResponse::tryFrom($response['code'])->statusMessage());
            } else {
                $this->response['create_group'] = [
                    'code' => $response['code'],
                    'error' => $response['body']['error']
                ];
            }
        }

        $this->response['create_group'] = [
            'code' => $response['code'],
            'data' => $response['body']['group']
        ];

        return $this;
    }

    /**
     * Get information about a specific group.
     *
     * @param string $groupName The name of the group.
     *
     * @return Groups Returns an instance of Groups for method chaining.
     */
    public function getGroup(string $groupName): Groups
    {
        $endpoint = Utils::useAPI('groups', 'get_group');
        $url = \str_replace('{organizationName}', $this->organizationName, $endpoint['url']);
        $url = \str_replace('{groupName}', $groupName, $url);
        $response = Utils::makeRequest($endpoint['method'], $url, $this->token);

        if ($response['code'] !== HttpResponse::OK->value) {
            if (php_sapi_name() === 'cli') {
                throw new PlatformError($response['body']['error'], HttpResponse::tryFrom($response['code'])->statusMessage());
            } else {
                $this->response['single_group'] = [
                    'code' => $response['code'],
                    'error' => $response['body']['error']
                ];
            }
        }
        $this->response['single_group'] = [
            'code' => $response['code'],
            'data' => $response['body']['group']
        ];

        return $this;
    }

    /**
     * Delete a specific group.
     *
     * @param string $groupName The name of the group.
     *
     * @return Groups Returns an instance of Groups for method chaining.
     */
    public function delete(string $groupName): Groups
    {
        $endpoint = Utils::useAPI('groups', 'delete');
        $url = \str_replace('{organizationName}', $this->organizationName, $endpoint['url']);
        $url = \str_replace('{groupName}', $groupName, $url);
        $response = Utils::makeRequest($endpoint['method'], $url, $this->token);

        if ($response['code'] !== HttpResponse::OK->value) {
            if (php_sapi_name() === 'cli') {
                throw new PlatformError($response['body']['error'], HttpResponse::tryFrom($response['code'])->statusMessage());
            } else {
                $this->response['deleted_group'] = [
                    'code' => $response['code'],
                    'error' => $response['body']['error']
                ];
            }
        }

        $this->response['deleted_group'] = [
            'code' => $response['code'],
            'data' => $response['body']['group']
        ];

        return $this;
    }

    /**
     * Add a location to a specific group.
     *
     * @param string $groupName The name of the group.
     * @param Location $location The code of the location to be added.
     *
     * @return Groups Returns an instance of Groups for method chaining.
     */
    public function addLocation(string $groupName, Location $location): Groups
    {
        $endpoint = Utils::useAPI('groups', 'add_location');
        $url = \str_replace('{organizationName}', $this->organizationName, $endpoint['url']);
        $url = \str_replace('{groupName}', $groupName, $url);
        $url = \str_replace('{location}', $location->value, $url);
        $response = Utils::makeRequest($endpoint['method'], $url, $this->token);

        if ($response['code'] !== HttpResponse::OK->value) {
            if (php_sapi_name() === 'cli') {
                throw new PlatformError($response['body']['error'], HttpResponse::tryFrom($response['code'])->statusMessage());
            } else {
                $this->response['added_location'] = [
                    'code' => $response['code'],
                    'error' => $response['body']['error']
                ];
            }
        }

        $this->response['added_location'] = [
            'code' => $response['code'],
            'data' => $response['body']['group']
        ];

        return $this;
    }

    /**
     * Delete a location from a specific group.
     *
     * @param string $groupName The name of the group.
     * @param Location $location The code of the location to be deleted.
     *
     * @return Groups Returns an instance of Groups for method chaining.
     */
    public function deleteLocation(string $groupName, Location $location): Groups
    {
        $endpoint = Utils::useAPI('groups', 'delete_location');
        $url = \str_replace('{organizationName}', $this->organizationName, $endpoint['url']);
        $url = \str_replace('{groupName}', $groupName, $url);
        $url = \str_replace('{location}', $location->value, $url);
        $response = Utils::makeRequest($endpoint['method'], $url, $this->token);

        if ($response['code'] !== HttpResponse::OK->value) {
            if (php_sapi_name() === 'cli') {
                throw new PlatformError($response['body']['error'], HttpResponse::tryFrom($response['code'])->statusMessage());
            } else {
                $this->response['delete_location'] = [
                    'code' => $response['code'],
                    'error' => $response['body']['error']
                ];
            }
        }

        $this->response['delete_location'] = [
            'code' => $response['code'],
            'data' => $response['body']['group']
        ];

        return $this;
    }

    /**
     * Transfer a specific group to another organization.
     *
     * @param string $oldGroupName The name of the group to be transferred.
     * @param string $organization The name of the organization to transfer the group to.
     *
     * @return Groups Returns an instance of Groups for method chaining.
     */
    public function transfer(string $oldGroupName, string $organization): Groups
    {
        $endpoint = Utils::useAPI('groups', 'transfer');
        $url = \str_replace('{organizationName}', $this->organizationName, $endpoint['url']);
        $url = \str_replace('{groupName}', $oldGroupName, $url);
        $response = Utils::makeRequest($endpoint['method'], $url, $this->token, ['organization' => $organization]);

        if ($response['code'] !== HttpResponse::OK->value) {
            if (php_sapi_name() === 'cli') {
                throw new PlatformError($response['body']['error'], HttpResponse::tryFrom($response['code'])->statusMessage());
            } else {
                $this->response['transfer_group'] = [
                    'code' => $response['code'],
                    'error' => $response['body']['error']
                ];
            }
        }

        $this->response['transfer_group'] = [
            'code' => $response['code'],
            'data' => $response['body']
        ];

        return $this;
    }

    /**
     * Unarchive Group
     * 
     * Unarchive a group that has been archived due to inactivity.
     * 
     * Databases get archived after 10 days of inactivity for users on a free plan — [learn more](https://docs.turso.tech/features/scale-to-zero). You can unarchive inactive groups using the API.
     *
     * @param string $groupName The name of the group.
     *
     * @return Groups Returns an instance of Groups for method chaining.
     */
    public function unarchive(string $groupName): Groups
    {
        $endpoint = Utils::useAPI('groups', 'unarchive');
        $url = \str_replace('{organizationName}', $this->organizationName, $endpoint['url']);
        $url = \str_replace('{groupName}', $groupName, $url);
        $response = Utils::makeRequest($endpoint['method'], $url, $this->token);

        if ($response['code'] !== HttpResponse::OK->value) {
            if (php_sapi_name() === 'cli') {
                throw new PlatformError($response['body']['error'], HttpResponse::tryFrom($response['code'])->statusMessage());
            } else {
                $this->response['unarchive_group'] = [
                    'code' => $response['code'],
                    'error' => $response['body']['error']
                ];
            }
        }

        $this->response['unarchive_group'] = [
            'code' => $response['code'],
            'data' => $response['body']['group']
        ];

        return $this;
    }

    /**
     * Update the version of a specific group.
     * 
     * NOTE: 
     * This operation causes some amount of downtime to occur during the update process. 
     * The version of libSQL server is taken from the latest built docker image.
     *
     * @param string $groupName The name of the group.
     *
     * @return Groups Returns an instance of Groups for method chaining.
     */
    public function updateVersion(string $groupName): Groups
    {
        $endpoint = Utils::useAPI('groups', 'update_version');
        $url = \str_replace('{organizationName}', $this->organizationName, $endpoint['url']);
        $url = \str_replace('{groupName}', $groupName, $url);
        $response = Utils::makeRequest($endpoint['method'], $url, $this->token);

        if ($response['code'] !== HttpResponse::OK->value) {
            if (php_sapi_name() === 'cli') {
                throw new PlatformError($response['body']['error'], HttpResponse::tryFrom($response['code'])->statusMessage());
            } else {
                $this->response['update_group'] = [
                    'code' => $response['code'],
                    'error' => $response['body']['error']
                ];
            }
        }

        $this->response['update_group'] = [
            'code' => $response['code'],
            'data' => ''
        ];

        return $this;
    }

    /**
     * Create an access token for a specific group.
     *
     * @param string $groupName The name of the group.
     * @param string $expiration Optional. The expiration time for the access token (default: 'never').
     * @param Authorization $authorization Optional. The authorization level for the access token (default: 'full-access').
     * @param array<string> $attach_databases Optional. The list of databases to attach to the access token.
     *
     * @return Groups Returns an instance of Groups for method chaining.
     */
    public function createToken(
        string $groupName,
        string $expiration = 'never',
        Authorization $authorization = Authorization::FULL_ACCESS,
        array $attach_databases = []
    ): Groups {
        $endpoint = Utils::useAPI('groups', 'create_token');
        $url = \str_replace('{organizationName}', $this->organizationName, $endpoint['url']);
        $url = \str_replace('{groupName}', $groupName, $url);
        $url = $url . "?" . \http_build_query([
            'expiration' => $expiration,
            'authorization' => $authorization->value
        ]);

        $body = [];
        if (!empty($attach_databases)) {
            array_merge($body, [
                'permissions' => [
                    'read_attach' => [
                        'databases' => $attach_databases
                    ]
                ]
            ]);
        }

        $response = Utils::makeRequest($endpoint['method'], $url, $this->token, $body);

        if ($response['code'] !== HttpResponse::OK->value) {
            if (php_sapi_name() === 'cli') {
                throw new PlatformError($response['body']['error'], HttpResponse::tryFrom($response['code'])->statusMessage());
            } else {
                $this->response['created_token_group'] = [
                    'code' => $response['code'],
                    'error' => $response['body']['error']
                ];
            }
        }

        $this->response['created_token_group'] = [
            'code' => $response['code'],
            'data' => $response['body']['jwt']
        ];

        return $this;
    }

    /**
     * Invalidate access tokens for a specific group.
     *
     * @param string $groupName The name of the group.
     *
     * @return Groups Returns an instance of Groups for method chaining.
     */
    public function invalidateTokens(string $groupName): Groups
    {
        $endpoint = Utils::useAPI('groups', 'invalidate_tokens');
        $url = \str_replace('{organizationName}', $this->organizationName, $endpoint['url']);
        $url = \str_replace('{groupName}', $groupName, $url);
        $response = Utils::makeRequest($endpoint['method'], $url, $this->token);

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

        $this->response['invalidated_token_group'] = [
            'code' => $response['code'],
            'data' => ''
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
            isset($this->response['list_groups']) => $this->response['list_groups'],
            isset($this->response['create_group']) => $this->response['create_group'],
            isset($this->response['single_group']) => $this->response['single_group'],
            isset($this->response['deleted_group']) => $this->response['deleted_group'],
            isset($this->response['added_location']) => $this->response['added_location'],
            isset($this->response['delete_location']) => $this->response['delete_location'],
            isset($this->response['transfer_group']) => $this->response['transfer_group'],
            isset($this->response['unarchive_group']) => $this->response['unarchive_group'],
            isset($this->response['update_group']) => $this->response['update_group'],
            isset($this->response['created_token_group']) => $this->response['created_token_group'],
            isset($this->response['invalidated_token_group']) => $this->response['invalidated_token_group'],
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
