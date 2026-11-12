<?php

declare(strict_types=1);

namespace Darkterminal\TursoPlatformAPI\Enums;

/** Member/invite role (contract enum for members & invites operations). */
enum MemberRole: string
{
    case ADMIN = 'admin';
    case MEMBER = 'member';
    case VIEWER = 'viewer';
}
