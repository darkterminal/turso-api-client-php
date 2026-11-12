<?php

declare(strict_types=1);

namespace Darkterminal\TursoPlatformAPI\Exceptions;

/** 401 / 403 — token rejected. */
class AuthenticationException extends HttpException {}
