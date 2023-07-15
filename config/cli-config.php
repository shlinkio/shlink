<?php

declare(strict_types=1);

use Doctrine\Migrations\Configuration\EntityManager\ExistingEntityManager;
use Doctrine\Migrations\Configuration\Migration\ConfigurationArray;
use Doctrine\Migrations\DependencyFactory;

// This file is currently used by doctrine migrations only

return (static function () {
    $migrationsConfig = [
        'migrations_paths' => [
            'ShlinkMigrations' => 'data/migrations',
        ],
        'table_storage' => [
            'table_name' => 'migrations',
        ],
        'custom_template' => 'data/migrations_template.txt',
    ];
    $em = include __DIR__ . '/entity-manager.php';

    return DependencyFactory::fromEntityManager(
        new ConfigurationArray($migrationsConfig),
        new ExistingEntityManager($em),
    );
})();
