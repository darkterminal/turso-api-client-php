<?php

if (!function_exists('isLocalDev')) {
    /**
     * Checks if the given database is a local development database.
     *
     * @param string|array $database The database to check. Can be a string or an array.
     *                              If an array, it should contain only one element.
     * @return bool Returns true if the database is a local development database, false otherwise.
     */
    function isLocalDev(string|array $database)
    {
        if (is_array($database)) {
            return count($database) === 1 && (preg_match('/127\.0\.0\.1|localhost/', $database[0]) || str_contains($database[0], 'http://'));
        }
        return preg_match('/127\.0\.0\.1|localhost/', $database) || str_contains($database, 'http://');
    }
}

if (!function_exists('platform_api_url')) {
    /**
     * Returns the base URL for the Turso API.
     *
     * @param string|null $path The path to append to the base URL.
     * @return string The base URL for the Turso API.
     */
    function platform_api_url(string $path = null)
    {
        $baseUrl = 'https://api.turso.tech/v1';
        return is_null($path) ? $baseUrl : "{$baseUrl}{$path}";
    }
}
