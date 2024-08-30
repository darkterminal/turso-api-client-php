<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInitbde9a5cb8bda43e8dcf07093eaaf44b7
{
    public static $files = array (
        'c185c186fe48ba3d303b99a842ac8527' => __DIR__ . '/../..' . '/src/core/helpers.php',
    );

    public static $prefixLengthsPsr4 = array (
        'D' => 
        array (
            'Darkterminal\\TursoPlatformAPI\\' => 30,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Darkterminal\\TursoPlatformAPI\\' => 
        array (
            0 => __DIR__ . '/../..' . '/src',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
        'Darkterminal\\TursoPlatformAPI\\Client' => __DIR__ . '/../..' . '/src/Client.php',
        'Darkterminal\\TursoPlatformAPI\\core\\Enums\\Authorization' => __DIR__ . '/../..' . '/src/core/Enums/Authorization.php',
        'Darkterminal\\TursoPlatformAPI\\core\\Enums\\Extension' => __DIR__ . '/../..' . '/src/core/Enums/Extension.php',
        'Darkterminal\\TursoPlatformAPI\\core\\Enums\\HttpResponse' => __DIR__ . '/../..' . '/src/core/Enums/HttpResponse.php',
        'Darkterminal\\TursoPlatformAPI\\core\\Enums\\InvoiceType' => __DIR__ . '/../..' . '/src/core/Enums/InvoiceType.php',
        'Darkterminal\\TursoPlatformAPI\\core\\Enums\\Location' => __DIR__ . '/../..' . '/src/core/Enums/Location.php',
        'Darkterminal\\TursoPlatformAPI\\core\\Enums\\RoleType' => __DIR__ . '/../..' . '/src/core/Enums/RoleType.php',
        'Darkterminal\\TursoPlatformAPI\\core\\PlatformError' => __DIR__ . '/../..' . '/src/core/PlatformError.php',
        'Darkterminal\\TursoPlatformAPI\\core\\Platforms\\APITokens' => __DIR__ . '/../..' . '/src/core/Platforms/APITokens.php',
        'Darkterminal\\TursoPlatformAPI\\core\\Platforms\\AuditLogs' => __DIR__ . '/../..' . '/src/core/Platforms/AuditLogs.php',
        'Darkterminal\\TursoPlatformAPI\\core\\Platforms\\Databases' => __DIR__ . '/../..' . '/src/core/Platforms/Databases.php',
        'Darkterminal\\TursoPlatformAPI\\core\\Platforms\\Groups' => __DIR__ . '/../..' . '/src/core/Platforms/Groups.php',
        'Darkterminal\\TursoPlatformAPI\\core\\Platforms\\Invites' => __DIR__ . '/../..' . '/src/core/Platforms/Invites.php',
        'Darkterminal\\TursoPlatformAPI\\core\\Platforms\\Locations' => __DIR__ . '/../..' . '/src/core/Platforms/Locations.php',
        'Darkterminal\\TursoPlatformAPI\\core\\Platforms\\Members' => __DIR__ . '/../..' . '/src/core/Platforms/Members.php',
        'Darkterminal\\TursoPlatformAPI\\core\\Platforms\\Organizations' => __DIR__ . '/../..' . '/src/core/Platforms/Organizations.php',
        'Darkterminal\\TursoPlatformAPI\\core\\Repositories\\AuditLogsRepository' => __DIR__ . '/../..' . '/src/core/Repositories/AuditLogsRepository.php',
        'Darkterminal\\TursoPlatformAPI\\core\\Repositories\\DatabaseRepository' => __DIR__ . '/../..' . '/src/core/Repositories/DatabaseRepository.php',
        'Darkterminal\\TursoPlatformAPI\\core\\Repositories\\Endpoints' => __DIR__ . '/../..' . '/src/core/Repositories/Endpoints.php',
        'Darkterminal\\TursoPlatformAPI\\core\\Repositories\\GroupRepository' => __DIR__ . '/../..' . '/src/core/Repositories/GroupRepository.php',
        'Darkterminal\\TursoPlatformAPI\\core\\Repositories\\InviteRepository' => __DIR__ . '/../..' . '/src/core/Repositories/InviteRepository.php',
        'Darkterminal\\TursoPlatformAPI\\core\\Repositories\\LocationRepository' => __DIR__ . '/../..' . '/src/core/Repositories/LocationRepository.php',
        'Darkterminal\\TursoPlatformAPI\\core\\Repositories\\MemberRepository' => __DIR__ . '/../..' . '/src/core/Repositories/MemberRepository.php',
        'Darkterminal\\TursoPlatformAPI\\core\\Repositories\\OrganizationRepository' => __DIR__ . '/../..' . '/src/core/Repositories/OrganizationRepository.php',
        'Darkterminal\\TursoPlatformAPI\\core\\Repositories\\TokenRepository' => __DIR__ . '/../..' . '/src/core/Repositories/TokenRepository.php',
        'Darkterminal\\TursoPlatformAPI\\core\\Response' => __DIR__ . '/../..' . '/src/core/Response.php',
        'Darkterminal\\TursoPlatformAPI\\core\\Utils' => __DIR__ . '/../..' . '/src/core/Utils.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInitbde9a5cb8bda43e8dcf07093eaaf44b7::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInitbde9a5cb8bda43e8dcf07093eaaf44b7::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInitbde9a5cb8bda43e8dcf07093eaaf44b7::$classMap;

        }, null, ClassLoader::class);
    }
}