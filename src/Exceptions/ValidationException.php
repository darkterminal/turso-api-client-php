<?php

declare(strict_types=1);

namespace Darkterminal\TursoPlatformAPI\Exceptions;

/** The API rejected local input before transport (invalid name, invalid seed, unsupported configuration keys, ...). */
class ValidationException extends PlatformException {}
