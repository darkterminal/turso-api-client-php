<?php

declare(strict_types=1);

namespace Darkterminal\TursoPlatformAPI\Exceptions;

/** The response could not be decoded as expected (not JSON, missing keys, wrong types). */
class UnexpectedResponseException extends PlatformException {}
