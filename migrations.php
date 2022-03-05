<?php

declare(strict_types=1);

use const Shlinkio\Shlink\MIGRATIONS_TABLE;

return [

    'migrations_paths' => [
        'ShlinkMigrations' => 'data/migrations',
    ],
    'table_storage' => [
        'table_name' => MIGRATIONS_TABLE,
    ],
    'custom_template' => 'data/migrations_template.txt',

];
