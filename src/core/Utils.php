<?php

namespace Darkterminal\TursoPlatformAPI\core;

use Darkterminal\TursoPlatformAPI\core\Enums\HttpResponse;
use Darkterminal\TursoPlatformAPI\core\Repositories\Endpoints;
use Exception;

/**
 * Class Utils
 *
 * Utility class containing various static methods for common tasks.
 */
final class Utils
{
    /**
     * Get API endpoint configuration.
     *
     * @param string $type The type of the API.
     * @param string $action The action to perform on the API.
     *
     * @return array The endpoint configuration for the specified type and action.
     *
     * @throws Exception Throws an exception with a 403 HTTP response code if the endpoint configuration is not found.
     */
    public static function useAPI($type, $action): array
    {
        $endpoints = Endpoints::use($type, $action);

        if (empty($endpoints)) {
            throw new Exception("Endpoint configuration not found for $type/$action");
        }

        return $endpoints;
    }

    /**
     * Validate a member role.
     *
     * @param string $roleName The name of the member role.
     *
     * @throws Exception Throws an exception with a 403 HTTP response code if the role is not valid.
     */
    public static function validateMemberRole(string $roleName): void
    {
        $roles = ['owner', 'admin', 'member'];
        if (!in_array($roleName, $roles)) {
            throw new Exception("The role is not valid, role options: owner, admin, or member");
        }
    }

    /**
     * Validate a user role.
     *
     * @param string $roleName The name of the user role.
     *
     * @throws Exception Throws an exception with a 403 HTTP response code if the role is not valid.
     */
    public static function validateUserRole(string $roleName): void
    {
        $roles = ['admin', 'member'];
        if (!in_array($roleName, $roles)) {
            throw new Exception("The role is not valid, role options: owner, admin, or member");
        }
    }

    /**
     * Perform a cURL request.
     *
     * @param string $method The HTTP request method (e.g., "GET", "POST").
     * @param string $url The URL to send the request to.
     * @param string|null $authToken The token to send the request to.
     * @param array $data Optional. The data to be sent with the request (used in POST and PUT requests).
     *
     * @return mixed Returns the decoded JSON response as an associative array on success, or a string containing the cURL error on failure.
     */
    public static function makeRequest(string $method, string $url, string|null $authToken, array $data = []): array
    {
        $headers = [
            "Content-Type: application/json",
        ];

        if (!is_null($authToken)) {
            $headers[] = "Authorization: Bearer $authToken";
        }

        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_NOBODY => false,
            CURLOPT_HEADER => true,
        ]);

        if (($method === 'POST' || $method === 'PUT' || $method === 'PATCH') && !empty($data)) {
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $response = curl_exec($curl);
        $headerSize = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
        $responseCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $body = substr($response, $headerSize);
        $err = curl_error($curl);
        curl_close($curl);

        if ($err) {
            return [
                'code' => HttpResponse::INTERNAL_SERVER_ERROR,
                'error' => $err
            ];
        } else {
            return [
                'code' => $responseCode,
                'body' => self::isJson($body) ? json_decode($body, true) : $body
            ];
        }
    }

    /**
     * Upload a file using cURL.
     *
     * @param string $url The URL to upload the file to.
     * @param string $token The authentication token.
     * @param string $filePath The path to the file to be uploaded.
     *
     * @return mixed Returns the decoded JSON response as an associative array on success, or a string containing the cURL error on failure.
     */
    public static function uploadDump(string $url, string $token, string $filePath): string|array
    {
        $headers = [
            "Authorization: Bearer $token",
        ];

        $postData = [
            'file' => new \CURLFile($filePath),
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_NOBODY, false);

        $response = curl_exec($ch);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $responseCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $body = substr($response, $headerSize);
        $err = curl_error($ch);
        curl_close($ch);

        if ($err) {
            return [
                'code' => HttpResponse::INTERNAL_SERVER_ERROR,
                'error' => $err
            ];
        } else {
            return [
                'code' => $responseCode,
                'body' => self::isJson($body) ? json_decode($body, true) : $body
            ];
        }
    }

    /**
     * Get the closest region using a cURL request.
     *
     * @param string $token The authentication token.
     *
     * @return array Returns the closest region information as an associative array.
     */
    public static function closestRegion(string $token): array
    {
        return self::makeRequest('GET', 'https://region.turso.io', $token);
    }

    /**
     * Parses a DSN string and returns an associative array of its components.
     *
     * @param string $dsn
     * @return array
     */
    public static function parseDsn(string $dsn): array
    {
        $result = [];
        $components = preg_split('/[&;]/', $dsn);

        if (isLocalDev($components)) {
            $result['dbname'] = $components[0];
            return $result;
        }

        foreach ($components as $component) {
            $parts = explode('=', $component, 2);
            if (count($parts) === 2) {
                $result[$parts[0]] = $parts[1];
            }
        }

        return $result;
    }

    public static function isJson(string $string): bool
    {
        if (function_exists('json_validate')) {
            return json_validate($string);
        }

        json_decode($string);
        return json_last_error() === JSON_ERROR_NONE;
    }

    /**
     * Removes objects from the results array where response.type is "close".
     *
     * @param array $data The input array.
     * @return array The modified array.
     */
    public static function removeCloseResponses(array $data): array
    {
        if (isset($data) && is_array($data)) {
            $data = array_filter($data, function ($result) {
                return !(
                    isset($result['type']) && $result['type'] === 'ok' &&
                    isset($result['response']['type']) && $result['response']['type'] === 'close'
                );
            });
            // Re-index the array
            $data = array_values($data);
        }

        if ($data[0]['type'] === 'error') {
            throw new PlatformError($data[0]['error']['message'], $data[0]['error']['code']);
        }
        return $data[0]['response']['result'];
    }

    /**
     * Checks if an array is associative.
     *
     * @param array $array
     * @return bool
     */
    public static function isArrayAssoc(array $array): bool
    {
        if ([] === $array)
            return false;
        return array_keys($array) !== range(0, count($array) - 1);
    }

    /**
     * Creates the directory and file for a given path if they don't exist.
     *
     * @param string $relativePath The relative path for the file.
     * @param bool $createFile Whether to create the file if it doesn't exist.
     * @return void
     */
    public static function createDirectoryAndFile(string $relativePath, bool $createFile = true): void
    {
        $directory = dirname($relativePath);

        if (!is_dir($directory)) {
            if (!mkdir($directory, 0755, true)) {
                die("Failed to create directory: $directory");
            }
        }

        if ($createFile && !file_exists($relativePath)) {
            if (!touch($relativePath)) {
                die("Failed to create file: $relativePath");
            }
        }
    }

    /**
     * Extracts the user home directory from a given path by retrieving the first and second path segments.
     *
     * @return string|null The user home directory or null if the path is invalid.
     */
    public static function getUserHomeDirectory(): ?string
    {
        $normalizedPath = preg_replace('#/+#', '/', __DIR__);
        $pathSegments = explode('/', trim($normalizedPath, '/'));

        if (count($pathSegments) >= 2) {
            return "/{$pathSegments[0]}/{$pathSegments[1]}";
        }

        return null;
    }
}
