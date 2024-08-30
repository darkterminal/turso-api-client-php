<?php

namespace Darkterminal\TursoPlatformAPI;

use Darkterminal\TursoPlatformAPI\core\Platforms\APITokens;
use Darkterminal\TursoPlatformAPI\core\Platforms\AuditLogs;
use Darkterminal\TursoPlatformAPI\core\Platforms\Databases;
use Darkterminal\TursoPlatformAPI\core\Platforms\Groups;
use Darkterminal\TursoPlatformAPI\core\Platforms\Invites;
use Darkterminal\TursoPlatformAPI\core\Platforms\Locations;
use Darkterminal\TursoPlatformAPI\core\Platforms\Members;
use Darkterminal\TursoPlatformAPI\core\Platforms\Organizations;

class Client
{
    public string $org;
    protected string $token;

    /**
     * Client constructor.
     *
     * @param string $org The name of the organization.
     * @param string $token The API token used for authentication.
     */
    public function __construct(string $org, string $token)
    {
        $this->org = $org;
        $this->token = $token;
    }

    /**
     * @return Databases Returns an instance of Databases for method chaining.
     *                    Use this to manage databases in your organization.
     */
    public function databases(): Databases
    {
        return new Databases($this->token, $this->org);
    }

    /**
     * @return Groups Returns an instance of Groups for method chaining.
     *                Use this to manage groups in your organization.
     */
    public function groups(): Groups
    {
        return new Groups($this->token, $this->org);
    }

    /**
     * @return Locations Returns an instance of Locations for method chaining.
     *                   Use this to manage locations in your organization.
     */
    public function locations(): Locations
    {
        return new Locations($this->token);
    }

    /**
     * @return Organizations Returns an instance of Organizations for method chaining.
     *                       Use this to manage organizations in your account.
     */
    public function organizations(): Organizations
    {
        return new Organizations($this->token);
    }

    /**
     * @return Members Returns an instance of Members for method chaining.
     *                  Use this to manage members in your organization.
     */
    public function members(): Members
    {
        return new Members($this->token, $this->org);
    }

    /**
     * @return Invites Returns an instance of Invites for method chaining.
     *                 Use this to invite users to your organization.
     */
    public function invites(): Invites
    {
        return new Invites($this->token, $this->org);
    }

    /**
     * @return AuditLogs Returns an instance of AuditLogs for method chaining.
     *                   Use this to retrieve audit logs for your organization.
     */
    public function auditLogs(): AuditLogs
    {
        return new AuditLogs($this->token, $this->org);
    }

    /**
     * @return APITokens Returns an instance of APITokens for method chaining.
     *                   Use this to create, list, validate, or revoke API tokens.
     */
    public function apiTokens(): APITokens
    {
        return new APITokens($this->token);
    }
}
