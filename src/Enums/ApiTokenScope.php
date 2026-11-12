<?php

declare(strict_types=1);

namespace Darkterminal\TursoPlatformAPI\Enums;

/** API token scopes (contract enum for scopes on create API token). */
enum ApiTokenScope: string
{
    case READ = 'read';
    case DB_CREATE = 'db:create';
    case DB_DELETE = 'db:delete';
    case DB_CONFIGURE = 'db:configure';
    case DB_MINT_TOKEN = 'db:mint-token';
    case DB_ROTATE_CREDS = 'db:rotate-creds';
    case GROUP_CONFIGURE = 'group:configure';
    case GROUP_MINT_TOKEN = 'group:mint-token';
    case GROUP_ROTATE_CREDS = 'group:rotate-creds';
    case GROUP_CREATE = 'group:create';
    case GROUP_DELETE = 'group:delete';
    case ORG_READ = 'org:read';
    case READ_ONLY = 'read-only';
    case FULL_ACCESS = 'full-access';
}
