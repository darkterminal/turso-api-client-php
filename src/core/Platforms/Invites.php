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
final class Invites implements Response
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
     * Get a list of invites for an organization.
     *
     * @return Invites Returns an instance of Invites for method chaining.
     */
    public function list(): Invites
    {
        $endpoint = Utils::useAPI('invites', 'invite_lists');
        $url = \str_replace('{organizationName}', $this->organizationName, $endpoint['url']);
        $response = Utils::makeRequest($endpoint['method'], $url, $this->token);

        if ($response['code'] !== HttpResponse::OK->value) {
            if (php_sapi_name() === 'cli') {
                throw new PlatformError($response['body']['error'], HttpResponse::tryFrom($response['code'])->statusMessage());
            } else {
                $this->response['list_invites'] = [
                    'code' => $response['code'],
                    'error' => $response['body']['error'],
                ];
            }
        }

        if (empty($response['body']['invites'])) {
            $response['code'] = HttpResponse::NOT_FOUND;
        }

        $this->response['list_invites'] = [
            'code' => $response['code'],
            'invites' => $response['body']['invites'],
        ];

        return $this;
    }

    /**
     * Create an invite for an organization.
     *
     * @param string $email The email address of the invitee.
     * @param RoleType $role The role of the invitee. Defaults to RoleType::MEMBER.
     *
     * @return Invites Returns an instance of Invites for method chaining.
     */
    public function createInvite(string $email, RoleType $role = RoleType::MEMBER): Invites
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new PlatformError('Invalid email address', 'CREATE_INVITE_FAILED');
        }

        $endpoint = Utils::useAPI('invites', 'create_invite');
        $url = \str_replace('{organizationName}', $this->organizationName, $endpoint['url']);
        Utils::validateMemberRole($role->value);
        $response = Utils::makeRequest($endpoint['method'], $url, $this->token, [
            'email' => $email,
            'role' => $role->value,
        ]);

        if ($response['code'] !== HttpResponse::OK->value) {
            if (php_sapi_name() === 'cli') {
                throw new PlatformError($response['body']['error'], HttpResponse::tryFrom($response['code'])->statusMessage());
            } else {
                $this->response['create_invite'] = [
                    'code' => $response['code'],
                    'error' => $response['body']['error'],
                ];
            }
        }

        $this->response['create_invite'] = [
            'code' => $response['code'],
            'data' => $response['body']['invited'],
        ];

        return $this;
    }

    /**
     * Delete an invite for an organization.
     *
     * @param string $email The email address of the invitee.
     *
     * @return Invites Returns an instance of Invites for method chaining.
     */
    public function deleteInvite(string $email): Invites
    {
        $endpoint = Utils::useAPI('invites', 'delete_invite');
        $url = \str_replace('{organizationName}', $this->organizationName, $endpoint['url']);
        $url = \str_replace('{email}', $email, $endpoint['url']);
        $response = Utils::makeRequest($endpoint['method'], $url, $this->token);

        if ($response['code'] !== HttpResponse::OK->value) {
            if (php_sapi_name() === 'cli') {
                throw new PlatformError($response['body']['error'], HttpResponse::tryFrom($response['code'])->statusMessage());
            } else {
                $this->response['delete_invite'] = [
                    'code' => $response['code'],
                    'error' => $response['body']['error'],
                ];
            }
        }

        $this->response['delete_invite'] = [
            'code' => $response['code'],
            'data' => '',
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
            isset($this->response['list_invites']) => $this->response['list_invites'],
            isset($this->response['create_invite']) => $this->response['create_invite'],
            isset($this->response['delete_invite']) => $this->response['delete_invite'],
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
