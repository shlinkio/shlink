<?php

declare(strict_types=1);

use Doctrine\ORM\Events;
use Happyr\DoctrineSpecification\Repository\EntitySpecificationRepository;
use Shlinkio\Shlink\Core\Config\EnvVars;
use Shlinkio\Shlink\Core\Visit\Listener\OrphanVisitsCountTracker;
use Shlinkio\Shlink\Core\Visit\Listener\ShortUrlVisitsCountTracker;

use function Shlinkio\Shlink\Core\ArrayUtils\contains;

return (static function (): array {
    $driver = EnvVars::DB_DRIVER->loadFromEnv();
    $useEncryption = (bool) EnvVars::DB_USE_ENCRYPTION->loadFromEnv();
    $isMysqlCompatible = contains($driver, ['maria', 'mysql']);

    $doctrineDriver = match ($driver) {
        'postgres' => 'pdo_pgsql',
        'mssql' => 'pdo_sqlsrv',
        default => 'pdo_mysql',
    };
    $readCredentialAsString = static function (EnvVars $envVar): string|null {
        $value = $envVar->loadFromEnv();
        return $value === null ? null : (string) $value;
    };
    $charset = match ($driver) {
        // This does not determine charsets or collations in tables or columns, but the charset used in the data
        // flowing in the connection, so it has to match what has been set in the database.
        'maria', 'mysql' => 'utf8mb4',
        'postgres' => 'utf8',
        default => null,
    };
    $driverOptions = match ($driver) {
        'mssql' => ['TrustServerCertificate' => 'true'],
        'maria', 'mysql' => ! $useEncryption ? [] : [
            1007 => true, // PDO::MYSQL_ATTR_SSL_KEY: Require using SSL
            1014 => false, // PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT: Trust any certificate
        ],
        'postgres' =>  ! $useEncryption ? [] : [
            'sslmode' => 'require', // Require connections to be encrypted
            'sslrootcert' => '', // Allow any certificate
        ],
        default => [],
    };
    $connection = match ($driver) {
        null, 'sqlite' => [
            'driver' => 'pdo_sqlite',
            'path' => 'data/database.sqlite',
        ],
        default => [
            'driver' => $doctrineDriver,
            'dbname' => EnvVars::DB_NAME->loadFromEnv(),
            'user' => $readCredentialAsString(EnvVars::DB_USER),
            'password' => $readCredentialAsString(EnvVars::DB_PASSWORD),
            'host' => EnvVars::DB_HOST->loadFromEnv(),
            'port' => EnvVars::DB_PORT->loadFromEnv(),
            'unix_socket' => $isMysqlCompatible ? EnvVars::DB_UNIX_SOCKET->loadFromEnv() : null,
            'charset' => $charset,
            'driverOptions' => $driverOptions,
        ],
    };

    return [

        'entity_manager' => [
            'orm' => [
                'proxies_dir' => 'data/proxies',
                'load_mappings_using_functional_style' => true,
                'default_repository_classname' => EntitySpecificationRepository::class,
                'listeners' => [
                    Events::onFlush => [ShortUrlVisitsCountTracker::class, OrphanVisitsCountTracker::class],
                    Events::postFlush => [ShortUrlVisitsCountTracker::class, OrphanVisitsCountTracker::class],
                ],
            ],
            'connection' => $connection,
        ],

    ];
})();
