<?php

declare(strict_types=1);

namespace Darkterminal\TursoPlatformAPI\Exceptions;

/**
 * The server responded with a non-2xx status. Carries status and body for diagnostics.
 * Credentials are NEVER attached to exceptions.
 */
class HttpException extends PlatformException
{
    public function __construct(
        string $message,
        public readonly int $statusCode,
        public readonly string $body = '',
        public readonly ?string $errorCode = null,
    ) {
        parent::__construct("$message (HTTP $statusCode)" . ($errorCode !== null ? " [$errorCode]" : ''));
    }
}
