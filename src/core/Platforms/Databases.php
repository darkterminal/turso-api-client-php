<?php
declare(strict_types=1);

namespace Darkterminal\TursoPlatformAPI\core\Platforms;

use Darkterminal\TursoPlatformAPI\core\Enums\Authorization;
use Darkterminal\TursoPlatformAPI\core\Enums\HttpResponse;
use Darkterminal\TursoPlatformAPI\core\Enums\Location;
use Darkterminal\TursoPlatformAPI\core\PlatformError;
use Darkterminal\TursoPlatformAPI\core\Response;
use Darkterminal\TursoPlatformAPI\core\Utils;

/**
 * Class Databases
 *
 * Represents a class for managing databases.
 */
final class Databases implements Response
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
     * Databases constructor.
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
     * List databases belonging to the organization or user.
     *
     * @param string $group Optional. Filter databases by group name.
     * @param string $schema Optional. The name of the parent database that owns the schema for this database. See [Multi-DB Schemas](https://docs.turso.tech/features/multi-db-schemas).
     *
     * @return Databases Returns an instance of Databases for method chaining.
     */
    public function list(string $group = '', string $schema = ''): Databases
    {
        $endpoint = Utils::useAPI('databases', 'list');
        $url = \str_replace('{organizationName}', $this->organizationName, $endpoint['url']);
        $url = (!empty($group) && !empty($schema)) ? "$url?" . http_build_query(['group' => $group, 'schema' => $schema]) : $url;
        $response = Utils::makeRequest($endpoint['method'], $url, $this->token);

        if ($response['code'] !== HttpResponse::OK->value) {
            if (php_sapi_name() === 'cli') {
                throw new PlatformError('Failed to get list of databases', 'GET_DATABASES_FAILED');
            } else {
                $this->response['list_databases'] = [
                    'code' => $response['code'],
                    'error' => $response['body']['error']
                ];
            }
        }

        $this->response['list_databases'] = [
            'code' => $response['code'],
            'data' => $response['body']['databases']
        ];

        return $this;
    }

    /**
     * Creates a new database.
     *
     * @param string $databaseName      The name of the new database. Must contain only lowercase letters, numbers, dashes. No longer than 64 characters. [Required]
     * @param bool   $isSchema          Mark this database as the parent schema database that updates child databases with any schema changes. See [Multi-DB Schemas](https://docs.turso.tech/features/multi-db-schemas).
     * @param string $schema            The name of the parent database to use as the schema. See [Multi-DB Schemas](https://docs.turso.tech/features/multi-db-schemas).
     * @param string $group             The name of the group where the database should be created. The group must already exist. [Required]
     * @param string $size_limit        The maximum size of the database in bytes. Values with units are also accepted, e.g. 1mb, 256mb, 1gb.
     * @param object $seed              The seed object to be used for creating a new database. 
     *                                  - type: enum<string> The type of seed to be used to create a new database. Available options: database, dump.
     *                                  - name: string The name of the existing database when database is used as a seed type.
     *                                  - url: string The URL returned by upload dump can be used with the dump seed type.
     *                                  - timestamp: string A formatted ISO 8601 recovery point to create a database from. This must be within the last 24 hours, or 30 days on the scaler plan.
     *
     * @throws PlatformError If the database name is invalid or the seed structure is invalid.
     * @return Databases Returns an instance of Databases for method chaining.
     */
    public function create(
        string $databaseName,
        bool $isSchema = false,
        string $schema = '',
        string $group = 'default',
        string $size_limit = '',
        array $seed = []
    ): Databases {
        $params = [];

        if (preg_match('/^[a-z0-9-]{1,64}$/', $databaseName) !== 1) {
            throw new PlatformError('Invalid database name, only lowercase alphanumeric characters and hyphens are allowed.', 'INVALID_DATABASE_NAME');
        }

        $params['name'] = $databaseName;

        $endpoint = Utils::useAPI('databases', 'create');
        $url = \str_replace('{organizationName}', $this->organizationName, $endpoint['url']);

        $groups = new Groups($this->token, $this->organizationName);
        $response = $groups->getGroup($group)->get();

        if (HttpResponse::tryFrom($response['code'])->statusMessage() !== 'OK') {
            $created = $groups->create($group)->get();
            $params['group'] = $created['data']['name'];
        } else {
            $params['group'] = $group;
        }

        if (!empty($seed)) {
            if (!isset($seed['type']) || !isset($seed['name']) || !isset($seed['url']) || !isset($seed['timestamp'])) {
                throw new PlatformError('Invalid seed structure.', 'INVALID_SEED_STRUCTURE');
            }
            $params['seed'] = $seed;
        }

        if (!empty($size_limit)) {
            $params['size_limit'] = $size_limit;
        }

        if ($isSchema) {
            $params['is_schema'] = $isSchema;
        }

        if (!empty($schema)) {
            $params['schema'] = $schema;
        }

        $response = Utils::makeRequest($endpoint['method'], $url, $this->token, $params);

        if ($response['code'] !== HttpResponse::OK->value) {
            if (php_sapi_name() === 'cli') {
                throw new PlatformError($response['body']['error'], HttpResponse::tryFrom($response['code'])->statusMessage());
            } else {
                $this->response['created_database'] = [
                    'code' => $response['code'],
                    'error' => $response['body']['error']
                ];
            }
        }

        $this->response['created_database'] = [
            'code' => $response['code'],
            'data' => $response['body']['database']
        ];
        return $this;
    }

    /**
     * Returns all databases that are a child of the given database name.
     *
     * @param string $databaseName The name of the database.
     * @return Databases An instance of the Databases class.
     */
    public function getChildOfDatabases(string $databaseName): Databases
    {
        $allDatabases = $this->list()->get();

        $parentDatabaseId = array_filter(array_map(function ($database) use ($databaseName) {
            if ($database['Name'] === $databaseName) {
                return $database['DbId'];
            }
        }, $allDatabases['data']));
        $parentDatabaseId = current($parentDatabaseId);

        $childDatabases = [];
        foreach ($allDatabases['data'] as $database) {
            if ($database['schema'] === $parentDatabaseId) {
                $database['parentSchema'] = $databaseName;
                $childDatabases[] = $database;
            }
        }

        unset($this->response);
        $this->response['child_databases'] = empty($childDatabases) ? [
            'code' => HttpResponse::NOT_FOUND,
            'data' => []
        ] : [
            'code' => HttpResponse::OK,
            'data' => $childDatabases
        ];
        return $this;
    }

    /**
     * Creates a parent schema for the given organization and database.
     *
     * @param string $databaseName The name of the database.
     * @return Databases The instance of the Databases class.
     */
    public function createParentSchema(string $databaseName): Databases
    {
        return $this->create(
            databaseName: $databaseName,
            isSchema: true
        );
    }

    /**
     * Creates a child schema for the given organization and database.
     *
     * @param string $databaseName The name of the database.
     * @param string $parentSchema The name of the parent schema.
     * @return Databases The instance of the Databases class.
     */
    public function createChildSchema(string $databaseName, string $parentSchema): Databases
    {
        return $this->create(
            databaseName: $databaseName,
            isSchema: false,
            schema: $parentSchema
        );
    }

    /**
     * Creates a child schema for the given organization and database with a specified size limit.
     *
     * @param string $databaseName The name of the database.
     * @param string $parentSchema The name of the parent schema.
     * @param string $size_limit The size limit for the schema.
     * @return Databases The instance of the Databases class.
     */
    public function craeteChildSchemaWithLimit(string $databaseName, string $parentSchema, string $size_limit): Databases
    {
        return $this->create(
            databaseName: $databaseName,
            isSchema: false,
            schema: $parentSchema,
            size_limit: $size_limit
        );
    }

    /**
     * Creates a parent schema for the given organization and database in a specific group.
     *
     * @param string $organizationName The name of the organization.
     * @param string $databaseName The name of the database.
     * @param string $group The name of the group.
     * @return Databases The instance of the Databases class.
     */
    public function createParentSchemaInGroup(string $databaseName, string $group): Databases
    {
        return $this->create(
            databaseName: $databaseName,
            isSchema: true,
            group: $group
        );
    }

    /**
     * Creates a child schema for the given organization and database in a specific group.
     *
     * @param string $databaseName The name of the database.
     * @param string $parentSchema The name of the parent schema.
     * @param string $group The name of the group.
     * @return Databases The instance of the Databases class.
     */
    public function createChildSchemaInGroup(string $databaseName, string $parentSchema, string $group): Databases
    {
        return $this->create(
            databaseName: $databaseName,
            isSchema: false,
            schema: $parentSchema,
            group: $group
        );
    }

    /**
     * Creates a child schema for the given organization and database in a specific group with a size limit.
     *
     * @param string $databaseName The name of the database.
     * @param string $parentSchema The name of the parent schema.
     * @param string $group The name of the group.
     * @param string $size_limit The size limit for the child schema.
     * @return Databases The instance of the Databases class.
     */
    public function createChildSchemaInGroupWithLimit(string $databaseName, string $parentSchema, string $group, string $size_limit): Databases
    {
        return $this->create(
            databaseName: $databaseName,
            isSchema: false,
            schema: $parentSchema,
            group: $group,
            size_limit: $size_limit
        );
    }

    /**
     * Creates a database for the given organization in a specific group.
     *
     * @param string $databaseName The name of the database.
     * @param string $group The name of the group.
     * @return Databases The instance of the Databases class.
     */
    public function createInGroup(string $databaseName, string $group): Databases
    {
        return $this->create(
            databaseName: $databaseName,
            group: $group
        );
    }

    /**
     * Creates a database for the given organization in a specific group with a specified size limit.
     *
     * @param string $databaseName The name of the database.
     * @param string $group The name of the group.
     * @param string $size_limit The size limit of the database.
     * @return Databases The instance of the Databases class.
     */
    public function createInGroupWithLimit(string $databaseName, string $group, string $size_limit): Databases
    {
        return $this->create(
            databaseName: $databaseName,
            group: $group,
            size_limit: $size_limit
        );
    }

    /**
     * Creates a database for the given organization with a specified size limit.
     *
     * @param string $databaseName The name of the database.
     * @param string $size_limit The size limit of the database.
     *
     * @return Databases The instance of the Databases class.
     */
    public function createWithLimit(string $databaseName, string $size_limit): Databases
    {
        return $this->create(
            databaseName: $databaseName,
            size_limit: $size_limit
        );
    }

    /**
     * Creates a database for the given organization from a seed.
     *
     * @param string $databaseName The name of the database.
     * @param array $seed The seed data for the database.
     * @return Databases The instance of the Databases class.
     */
    public function createFromSeed(string $databaseName, array $seed): Databases
    {
        if (empty($seed)) {
            throw new PlatformError('No seed data provided', 'INVALID_SEED_DATA');
        }

        return $this->create(
            databaseName: $databaseName,
            seed: $seed
        );
    }

    /**
     * Creates a database for the given organization from a seed in a specific group.
     *
     * @param string $databaseName The name of the database.
     * @param array $seed The seed data for the database.
     * @param string $group The name of the group where the database should be created.
     * @throws PlatformError If no seed data is provided.
     * @return Databases The instance of the Databases class.
     */
    public function createFromSeedInGroup(string $databaseName, array $seed, string $group): Databases
    {
        if (empty($seed)) {
            throw new PlatformError('No seed data provided', 'INVALID_SEED_DATA');
        }

        return $this->create(
            databaseName: $databaseName,
            seed: $seed,
            group: $group
        );
    }

    /**
     * Retrieve Database
     * 
     * Returns a database belonging to the organization or user.
     *
     * @param string $databaseName The name of the database.
     *
     * @return Databases Returns an instance of Databases for method chaining.
     */
    public function getDatabase(string $databaseName): Databases
    {
        $endpoint = Utils::useAPI('databases', 'retrieve');
        $url = \str_replace('{organizationName}', $this->organizationName, $endpoint['url']);
        $url = \str_replace('{databaseName}', $databaseName, $url);
        $response = Utils::makeRequest($endpoint['method'], $url, $this->token);

        if ($response['code'] !== HttpResponse::OK->value) {
            if (php_sapi_name() === 'cli') {
                throw new PlatformError($response['body']['error'], HttpResponse::tryFrom($response['code'])->statusMessage());
            } else {
                $this->response['retrieve_database'] = [
                    'code' => $response['code'],
                    'error' => $response['body']['error']
                ];
            }
        }
        $this->response['retrieve_database'] = [
            'code' => $response['code'],
            'data' => $response['body']['database']
        ];

        return $this;
    }

    /**
     * Retrieves the configuration of a database belonging to an organization.
     *
     * @param string $databaseName The name of the database.
     *
     * @return Databases Returns an instance of Databases for method chaining.
     */
    public function getDatabaseConfiguration(string $databaseName): Databases
    {
        $endpoint = Utils::useAPI('databases', 'retrieve_configuration');
        $url = \str_replace('{organizationName}', $this->organizationName, $endpoint['url']);
        $url = \str_replace('{databaseName}', $databaseName, $url);
        $response = Utils::makeRequest($endpoint['method'], $url, $this->token);

        if ($response['code'] !== HttpResponse::OK->value) {
            if (php_sapi_name() === 'cli') {
                throw new PlatformError($response['body']['error'], HttpResponse::tryFrom($response['code'])->statusMessage());
            } else {
                $this->response['retrieve_configuration'] = [
                    'code' => $response['code'],
                    'error' => $response['body']['error']
                ];
            }
        }

        $this->response['retrieve_configuration'] = [
            'code' => $response['code'],
            'data' => $response['body']
        ];

        return $this;
    }

    /**
     * Updates the configuration of a database belonging to an organization.
     *
     * Available configuration options:
     * - allow_attach: bool
     * - block_reads: bool
     * - block_writes: bool
     * - size_limit: string
     *
     * @param string $databaseName The name of the database.
     * @param array $configuration The new configuration for the database.
     *
     * @return Databases Returns an instance of Databases for method chaining.
     */
    public function updateDatabaseConfiguration(string $databaseName, array $configuration): Databases
    {
        $endpoint = Utils::useAPI('databases', 'update_configuration');
        $url = \str_replace('{organizationName}', $this->organizationName, $endpoint['url']);
        $url = \str_replace('{databaseName}', $databaseName, $url);

        $defaultConfiguration = [
            'allow_attach' => false,
            'block_reads' => false,
            'block_writes' => false,
            'size_limit' => '',
        ];

        $filteredConfiguration = array_intersect_key($configuration, $defaultConfiguration);
        $body = array_replace($defaultConfiguration, $filteredConfiguration);

        $response = Utils::makeRequest($endpoint['method'], $url, $this->token, $body);

        if ($response['code'] !== HttpResponse::OK->value) {
            if (php_sapi_name() === 'cli') {
                throw new PlatformError($response['body']['error'], HttpResponse::tryFrom($response['code'])->statusMessage());
            } else {
                $this->response['update_configuration'] = [
                    'code' => $response['code'],
                    'error' => $response['body']['error']
                ];
            }
        }

        $this->response['update_configuration'] = [
            'code' => $response['code'],
            'data' => $response['body']
        ];
        return $this;
    }

    /**
     * Retrieves the usage information for a specific database.
     *
     * @param string $databaseName The name of the database.
     * @throws PlatformError If the usage information cannot be fetched.
     * @return Databases Returns an instance of Databases for method chaining.
     */
    public function usage(string $databaseName): Databases
    {
        $endpoint = Utils::useAPI('databases', 'usage');
        $url = \str_replace('{organizationName}', $this->organizationName, $endpoint['url']);
        $url = \str_replace('{databaseName}', $databaseName, $url);
        $response = Utils::makeRequest($endpoint['method'], $url, $this->token);

        if ($response['code'] !== HttpResponse::OK->value) {
            if (php_sapi_name() === 'cli') {
                throw new PlatformError($response['body']['error'], HttpResponse::tryFrom($response['code'])->statusMessage());
            } else {
                $this->response['usage_database'] = [
                    'code' => $response['code'],
                    'error' => $response['body']['error']
                ];
            }
        }

        $this->response['usage_database'] = [
            'code' => $response['code'],
            'data' => $response['body']['database']
        ];

        return $this;
    }

    /**
     * Retrieves statistics for a specific database.
     *
     * @param string $databaseName The name of the database.
     * @throws PlatformError If the database statistics cannot be retrieved.
     * @return Databases Returns an instance of Databases for method chaining.
     */
    public function stats(string $databaseName): Databases
    {
        $endpoint = Utils::useAPI('databases', 'stats');
        $url = \str_replace('{organizationName}', $this->organizationName, $endpoint['url']);
        $url = \str_replace('{databaseName}', $databaseName, $url);
        $response = Utils::makeRequest($endpoint['method'], $url, $this->token);

        if ($response['code'] !== HttpResponse::OK->value) {
            if (php_sapi_name() === 'cli') {
                throw new PlatformError($response['body']['error'], HttpResponse::tryFrom($response['code'])->statusMessage());
            } else {
                $this->response['stats_database'] = [
                    'code' => $response['code'],
                    'error' => $response['body']['error']
                ];
            }
        }

        $this->response['stats_database'] = [
            'code' => $response['code'],
            'data' => $response['body']['top_queries']
        ];

        return $this;
    }

    /**
     * Deletes a specific database.
     *
     * @param string $databaseName The name of the database.
     * @throws PlatformError If the database deletion fails.
     * @return Databases Returns an instance of Databases for method chaining.
     */
    public function delete(string $databaseName): Databases
    {
        $endpoint = Utils::useAPI('databases', 'delete');
        $url = \str_replace('{organizationName}', $this->organizationName, $endpoint['url']);
        $url = \str_replace('{databaseName}', $databaseName, $url);
        $response = Utils::makeRequest($endpoint['method'], $url, $this->token);

        if ($response['code'] !== HttpResponse::OK->value) {
            if (php_sapi_name() === 'cli') {
                throw new PlatformError($response['body']['error'], HttpResponse::tryFrom($response['code'])->statusMessage());
            } else {
                $this->response['deleted_database'] = [
                    'code' => $response['code'],
                    'error' => $response['body']['error']
                ];
            }
        }

        $this->response['deleted_database'] = [
            'code' => $response['code'],
            'data' => $response['body']['database']
        ];

        return $this;
    }

    /**
     * List instances for a specific database.
     *
     * @param string $databaseName The name of the database.
     *
     * @return Databases Returns an instance of Databases for method chaining.
     */
    public function listInstances(string $databaseName): Databases
    {
        $endpoint = Utils::useAPI('databases', 'list_instances');
        $url = \str_replace('{organizationName}', $this->organizationName, $endpoint['url']);
        $url = \str_replace('{databaseName}', $databaseName, $url);
        $response = Utils::makeRequest($endpoint['method'], $url, $this->token);

        if ($response['code'] !== HttpResponse::OK->value) {
            if (php_sapi_name() === 'cli') {
                throw new PlatformError($response['body']['error'], HttpResponse::tryFrom($response['code'])->statusMessage());
            } else {
                $this->response['list_instance_databases'] = [
                    'code' => $response['code'],
                    'error' => $response['body']['error']
                ];
            }
        }

        $this->response['list_instance_databases'] = [
            'code' => $response['code'],
            'data' => $response['body']['instances']
        ];

        return $this;
    }

    /**
     * Get information about a specific database instance.
     *
     * @param string $databaseName The name of the database.
     * @param Location $instanceName The name of the instance (location code).
     *
     * @return Databases Returns an instance of Databases for method chaining.
     */
    public function getInstance(string $databaseName, Location $instanceName): Databases
    {
        $endpoint = Utils::useAPI('databases', 'retrieve_instance');
        $url = \str_replace('{organizationName}', $this->organizationName, $endpoint['url']);
        $url = \str_replace('{databaseName}', $databaseName, $url);
        $url = \str_replace('{instanceName}', $instanceName->value, $url);
        $response = Utils::makeRequest($endpoint['method'], $url, $this->token);

        if ($response['code'] !== HttpResponse::OK->value) {
            if (php_sapi_name() === 'cli') {
                throw new PlatformError($response['body']['error'], HttpResponse::tryFrom($response['code'])->statusMessage());
            } else {
                $this->response['instance_database'] = [
                    'code' => $response['code'],
                    'error' => $response['body']['error']
                ];
            }
        }

        $this->response['instance_database'] = [
            'code' => $response['code'],
            'data' => $response['body']['instance']
        ];

        return $this;
    }

    /**
     * Create an access token for a specific database.
     *
     * @param string $databaseName The name of the database.
     * @param string $expiration Optional. Expiration time for the token (e.g., 2w1d30m) (default: 'never').
     * @param Authorization $autorization Optional. Authorization level for the token (full-access or read-only) (default: 'full-access').
     * @param array<string> $attach_databases Optional. The list of databases to allows the token bearer to attach other databases within a transaction for read operations.
     *
     * @return Databases Returns an instance of Databases for method chaining.
     */
    public function createToken(
        string $databaseName,
        string $expiration = 'never',
        Authorization $autorization = Authorization::FULL_ACCESS,
        array $attach_databases = []
    ): Databases {
        $endpoint = Utils::useAPI('databases', 'create_token');
        $url = \str_replace('{organizationName}', $this->organizationName, $endpoint['url']);
        $url = \str_replace('{databaseName}', $databaseName, $url);
        $url = $url . "?" . \http_build_query([
            'expiration' => $expiration,
            'authorization' => $autorization->value
        ]);

        $body = [];
        if (!empty($attach_databases)) {
            array_push($body, [
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
                $this->response['create_token_database'] = [
                    'code' => $response['code'],
                    'error' => $response['body']['error']
                ];
            }
        }

        $this->response['created_token_database'] = [
            'code' => $response['code'],
            'data' => $response['body']['jwt']
        ];

        return $this;
    }

    /**
     * Invalidates all authorization tokens for the specified database.
     *
     * @param string $databaseName The name of the database.
     *
     * @return Databases Returns an instance of Databases for method chaining.
     */
    public function invalidateTokens(string $databaseName): Databases
    {
        $endpoint = Utils::useAPI('databases', 'invalidate_tokens');
        $url = \str_replace('{organizationName}', $this->organizationName, $endpoint['url']);
        $url = \str_replace('{databaseName}', $databaseName, $url);
        $response = Utils::makeRequest($endpoint['method'], $url, $this->token);

        if ($response['code'] !== HttpResponse::OK->value) {
            if (php_sapi_name() === 'cli') {
                throw new PlatformError($response['body']['error'], HttpResponse::tryFrom($response['code'])->statusMessage());
            } else {
                $this->response['invalidate_tokens_database'] = [
                    'code' => $response['code'],
                    'error' => $response['body']['error']
                ];
            }
        }

        $this->response['invalidated_tokens_database'] = [
            'code' => $response['code'],
            'data' => true
        ];

        return $this;
    }

    /**
     * Upload a database dump.
     *
     * @param string $filePath The path to the database dump file.
     *
     * @return Databases Returns an instance of Databases for method chaining.
     */
    public function uploadDump(string $filePath): Databases
    {
        $endpoint = Utils::useAPI('databases', 'upload_dump');
        $url = \str_replace('{organizationName}', $this->organizationName, $endpoint['url']);
        $response = Utils::uploadDump($url, $this->token, $filePath);

        if ($response['code'] !== HttpResponse::OK->value) {
            if (php_sapi_name() === 'cli') {
                throw new PlatformError($response['body']['error'], HttpResponse::tryFrom($response['code'])->statusMessage());
            } else {
                $this->response['uploaded_dump_database'] = [
                    'code' => $response['code'],
                    'error' => $response['body']['error']
                ];
            }
        }

        $this->response['uploaded_dump_database'] = [
            'code' => $response['code'],
            'data' => $response['body']['dump_url']
        ];

        return $this;
    }

    /**
     * Returns the results of the API response.
     *
     * @return array|string The results of the API response.
     */
    private function results(): array|string
    {
        return match (true) {
            isset($this->response['list_databases']) => $this->response['list_databases'],
            isset($this->response['created_database']) => $this->response['created_database'],
            isset($this->response['retrieve_database']) => $this->response['retrieve_database'],
            isset($this->response['retrieve_configuration']) => $this->response['retrieve_configuration'],
            isset($this->response['update_configuration']) => $this->response['update_configuration'],
            isset($this->response['usage_database']) => $this->response['usage_database'],
            isset($this->response['stats_database']) => $this->response['stats_database'],
            isset($this->response['deleted_database']) => $this->response['deleted_database'],
            isset($this->response['list_instance_databases']) => $this->response['list_instance_databases'],
            isset($this->response['instance_database']) => $this->response['instance_database'],
            isset($this->response['created_token_database']) => $this->response['created_token_database'],
            isset($this->response['invalidated_tokens_database']) => $this->response['invalidated_tokens_database'],
            isset($this->response['child_databases']) => $this->response['child_databases'],
            isset($this->response['uploaded_dump_database']) => $this->response['uploaded_dump_database'],
            default => $this->response,
        };
    }

    /**
     * Get the API response as an array.
     *
     * @return mixed The API response as an array.
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
