<?php

declare(strict_types=1);

return [
    'name' => 'ShlinkMigrations',
    'migrations_paths' => [
        'ShlinkMigrations' => 'data/migrations',
    ],
    'table_storage' => [
        'table_name' => 'migrations',
    ],
    'custom_template' => 'data/migrations_template.txt',
];
