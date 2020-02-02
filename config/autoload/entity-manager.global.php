<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Common;

return [

    'entity_manager' => [
        'orm' => [
            'proxies_dir' => 'data/proxies',
            'load_mappings_using_functional_style' => true,
        ],
        'connection' => [
            'user' => '',
            'password' => '',
            'dbname' => 'shlink',
            'charset' => 'utf8',
        ],
    ],

];
