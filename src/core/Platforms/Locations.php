<?php

namespace Darkterminal\TursoPlatformAPI\core\Platforms;

use Darkterminal\TursoPlatformAPI\core\Enums\HttpResponse;
use Darkterminal\TursoPlatformAPI\core\PlatformError;
use Darkterminal\TursoPlatformAPI\core\Response;
use Darkterminal\TursoPlatformAPI\core\Utils;

/**
 * Class Locations
 *
 * Represents a class for managing locations.
 */
final class Locations implements Response
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
     * Locations constructor.
     *
     * @param string $token The API token used for authentication.
     */
    public function __construct(string $token)
    {
        $this->token = $token;
    }

    /**
     * Get a list of available locations.
     *
     * @return Locations Returns an instance of Locations for method chaining.
     */
    public function getLocations(): Locations
    {
        $endpoint = Utils::useAPI('locations', 'list');
        $response = Utils::makeRequest($endpoint['method'], $endpoint['url'], $this->token);

        if ($response['code'] !== HttpResponse::OK->value) {
            if (php_sapi_name() === 'cli') {
                throw new PlatformError($response['body']['error'], HttpResponse::tryFrom($response['code'])->statusMessage());
            } else {
                $this->response['list_locations'] = [
                    'code' => $response['code'],
                    'error' => $response['body']['error'],
                ];
                return $this;
            }
        }

        $this->response['list_locations'] = [
            'code' => $response['code'],
            'data' => $response['body']['locations']
        ];

        return $this;
    }

    /**
     * Get the closest region based on the user's location.
     *
     * @return Locations Returns an instance of Locations for method chaining.
     */
    public function closestRegion(): Locations
    {
        $endpoint = Utils::useAPI('locations', 'closest_region');
        $response = Utils::makeRequest($endpoint['method'], $endpoint['url'], $this->token);

        if ($response['code'] !== HttpResponse::OK->value) {
            if (php_sapi_name() === 'cli') {
                throw new PlatformError($response['body']['error'], HttpResponse::tryFrom($response['code'])->statusMessage());
            } else {
                $this->response['closest_region'] = [
                    'code' => $response['code'],
                    'error' => $response['body']['error'],
                ];
                return $this;
            }
        }

        $this->response['closest_region'] = [
            'code' => $response['code'],
            'data' => $response['body']
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
            isset($this->response['list_locations']) => $this->response['list_locations'],
            isset($this->response['closest_region']) => $this->response['closest_region'],
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
