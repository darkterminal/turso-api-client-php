<?php

namespace Darkterminal\TursoPlatformAPI\core\Repositories;

class AuditLogsRepository
{
    public static function endpoints(): array
    {
        return [
            'method' => 'GET',
            'url' => platform_api_url('/organizations/{organizationName}/audit-logs')
        ];
    }
}
