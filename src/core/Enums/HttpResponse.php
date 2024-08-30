<?php

namespace Darkterminal\TursoPlatformAPI\core\Enums;

enum HttpResponse: int
{
    case IDK = 0;
    case OK = 200;
    case CREATED = 201;
    case ACCEPTED = 202;
    case NO_CONTENT = 204;
    case BAD_REQUEST = 400;
    case UNAUTHORIZED = 401;
    case PAYMENT_REQUIRED = 402;
    case FORBIDDEN = 403;
    case NOT_FOUND = 404;
    case NOT_ACCEPTABLE = 406;
    case CONFLICT = 409;
    case UNPROCESSABLE_ENTITY = 422;
    case INTERNAL_SERVER_ERROR = 500;
    case NOT_IMPLEMENTED = 501;
    case SERVICE_UNAVAILABLE = 503;

    public function statusMessage(): string
    {
        return match ($this) {
            self::IDK => "{$this->value} - IDK",
            self::OK => "{$this->value} - OK",
            self::CREATED => "{$this->value} - CREATED",
            self::ACCEPTED => "{$this->value} - ACCEPTED",
            self::NO_CONTENT => "{$this->value} - NO_CONTENT",
            self::BAD_REQUEST => "{$this->value} - BAD_REQUEST",
            self::UNAUTHORIZED => "{$this->value} - UNAUTHORIZED",
            self::PAYMENT_REQUIRED => "{$this->value} - PAYMENT_REQUIRED",
            self::FORBIDDEN => "{$this->value} - FORBIDDEN",
            self::NOT_FOUND => "{$this->value} - NOT_FOUND",
            self::NOT_ACCEPTABLE => "{$this->value} - NOT_ACCEPTABLE",
            self::CONFLICT => "{$this->value} - CONFLICT",
            self::UNPROCESSABLE_ENTITY => "{$this->value} - UNPROCESSABLE_ENTITY",
            self::INTERNAL_SERVER_ERROR => "{$this->value} - INTERNAL_SERVER_ERROR",
            self::NOT_IMPLEMENTED => "{$this->value} - NOT_IMPLEMENTED",
            self::SERVICE_UNAVAILABLE => "{$this->value} - SERVICE_UNAVAILABLE",
        };
    }
}
