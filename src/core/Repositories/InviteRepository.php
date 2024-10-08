<?php

namespace Darkterminal\TursoPlatformAPI\core\Repositories;

class InviteRepository
{
    public static function endpoints($action): array
    {
        $items = [
            'invite_lists' => [
                'method' => 'GET',
                'url' => platform_api_url('/organizations/{organizationName}/invites')
            ],
            'create_invite' => [
                'method' => 'POST',
                'url' => platform_api_url('/organizations/{organizationName}/invites')
            ],
            'delete_invite' => [
                'method' => 'DELETE',
                'url' => platform_api_url('/organizations/{organizationName}/invites/{email}')
            ]
        ];

        return $items[$action] ?? [];
    }
}
