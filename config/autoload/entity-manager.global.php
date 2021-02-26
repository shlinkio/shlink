<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Common;

use Happyr\DoctrineSpecification\Repository\EntitySpecificationRepository;

return [

    'entity_manager' => [
        'orm' => [
            'proxies_dir' => 'data/proxies',
            'load_mappings_using_functional_style' => true,
            'default_repository_classname' => EntitySpecificationRepository::class,
        ],
        'connection' => [
            'user' => '',
            'password' => '',
            'dbname' => 'shlink',
            'charset' => 'utf8',
        ],
    ],

];
