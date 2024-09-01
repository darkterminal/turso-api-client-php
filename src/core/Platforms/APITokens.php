<?php

namespace Darkterminal\TursoPlatformAPI\core\Platforms;

use Darkterminal\TursoPlatformAPI\core\Enums\HttpResponse;
use Darkterminal\TursoPlatformAPI\core\PlatformError;
use Darkterminal\TursoPlatformAPI\core\Response;
use Darkterminal\TursoPlatformAPI\core\Utils;

/**
 * Class APITokens
 *
 * Represents an API Tokens management class.
 */
final class APITokens implements Response
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
     * APITokens constructor.
     *
     * @param string $token The API token used for authentication.
     */
    public function __construct(string $token)
    {
        $this->token = $token;
    }

    /**
     * List API tokens.
     *
     * @return APITokens Returns an instance of APITokens for method chaining.
     */
    public function list(): APITokens
    {
        $endpoint = Utils::useAPI('tokens', 'list');
        $response = Utils::makeRequest($endpoint['method'], $endpoint['url'], $this->token);

        if ($response['code'] !== HttpResponse::OK->value) {
            if (php_sapi_name() === 'cli') {
                throw new PlatformError($response['body']['error'], HttpResponse::tryFrom($response['code'])->statusMessage());
            } else {
                $this->response['list_tokens'] = [
                    'code' => HttpResponse::NOT_FOUND,
                    'error' => $response['body']['error']
                ];
                return $this;
            }
        }
        
        $this->response['list_tokens'] = [
            'code' => HttpResponse::OK,
            'data' => $response['body']['tokens'] 
        ];

        return $this;
    }

    /**
     * Create a new API token.
     *
     * @param string $tokenName The name of the new API token.
     *
     * @return APITokens Returns an instance of APITokens for method chaining.
     */
    public function create(string $tokenName): APITokens
    {
        $endpoint = Utils::useAPI('tokens', 'create');
        $response = Utils::makeRequest($endpoint['method'], \str_replace('{tokenName}', $tokenName, $endpoint['url']), $this->token);

        if ($response['code'] !== HttpResponse::OK->value) {
            if (php_sapi_name() === 'cli') {
                throw new PlatformError($response['body']['error'], HttpResponse::tryFrom($response['code'])->statusMessage());
            } else {
                $this->response['list_tokens'] = [
                    'code' => HttpResponse::NOT_FOUND,
                    'error' => $response['body']['error']
                ];
                return $this;
            }
        }

        $this->response['create_token'] = [
            'code' => HttpResponse::OK,
            'data' => $response['body']
        ];

        return $this;
    }

    /**
     * Validate the API token.
     *
     * @return APITokens Returns an instance of APITokens for method chaining.
     */
    public function validate(): APITokens
    {
        $endpoint = Utils::useAPI('tokens', 'validate');
        $response = Utils::makeRequest($endpoint['method'], $endpoint['url'], $this->token);

        if ($response['code'] !== HttpResponse::OK->value) {
            if (php_sapi_name() === 'cli') {
                throw new PlatformError($response['body']['error'], HttpResponse::tryFrom($response['code'])->statusMessage());
            } else {
                $this->response['list_tokens'] = [
                    'code' => HttpResponse::NOT_FOUND,
                    'error' => $response['body']['error']
                ];
                return $this;
            }
        }

        $this->response['token_validate'] = [
            'code' => HttpResponse::OK,
            'data' => $response['body']['exp']
        ];

        return $this;
    }

    /**
     * Revoke an API token.
     *
     * @param string $tokenName The name of the API token to revoke.
     *
     * @return APITokens Returns an instance of APITokens for method chaining.
     */
    public function revoke(string $tokenName): APITokens
    {
        $endpoint = Utils::useAPI('tokens', 'revoke');
        $response = Utils::makeRequest($endpoint['method'], \str_replace('{tokenName}', $tokenName, $endpoint['url']), $this->token);

        if ($response['code'] !== HttpResponse::OK->value) {
            if (php_sapi_name() === 'cli') {
                throw new PlatformError($response['body']['error'], HttpResponse::tryFrom($response['code'])->statusMessage());
            } else {
                $this->response['list_tokens'] = [
                    'code' => HttpResponse::NOT_FOUND,
                    'error' => $response['body']['error']
                ];
                return $this;
            }
        }

        $this->response['revoke_token'] = [
            'code' => HttpResponse::OK,
            'data' => $response['body']['token']
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
            isset($this->response['list_tokens']) => $this->response['list_tokens'],
            isset($this->response['create_token']) => $this->response['create_token'],
            isset($this->response['token_validate']) => $this->response['token_validate'],
            isset($this->response['revoke_token']) => $this->response['revoke_token'],
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
