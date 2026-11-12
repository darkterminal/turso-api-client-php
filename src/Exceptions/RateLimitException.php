<?php

declare(strict_types=1);

namespace Darkterminal\TursoPlatformAPI\Exceptions;

/** 429 — rate limited. retryAfter is the server-provided hint (if any), in seconds. */
class RateLimitException extends HttpException
{
    public function __construct(
        string $message,
        public readonly ?int $retryAfter = null,
        string $body = '',
        ?string $errorCode = null,
    ) {
        parent::__construct($message, 429, $body, $errorCode);
    }
}
