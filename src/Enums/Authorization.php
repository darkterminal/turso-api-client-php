<?php

declare(strict_types=1);

namespace Darkterminal\TursoPlatformAPI\Enums;

/** Authorization levels for database tokens (contract enum for authorization query param). */
enum Authorization: string
{
    case FULL_ACCESS = 'full-access';
    case READ_ONLY = 'read-only';
}
