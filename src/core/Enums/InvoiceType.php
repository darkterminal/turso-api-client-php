<?php

namespace Darkterminal\TursoPlatformAPI\core\Enums;

enum InvoiceType: string
{
    case ALL = 'all';
    case UPCOMING = 'upcoming';
    case ISSUED = 'issued';
}
