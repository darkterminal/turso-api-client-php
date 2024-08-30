<?php

namespace Darkterminal\TursoPlatformAPI\core\Enums;

enum Authorization: string
{
    case READ_ONLY = 'read-only';
    case FULL_ACCESS = 'full-access';
}
