<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Common;

use Doctrine\ORM\EntityManager;

return [

    'entity_manager' => [
        'orm' => [
            'types' => [
                Type\ChronosDateTimeType::CHRONOS_DATETIME => Type\ChronosDateTimeType::class,
            ],
        ],
    ],

    'dependencies' => [
        'factories' => [
            EntityManager::class => Doctrine\EntityManagerFactory::class,
        ],
        'aliases' => [
            'em' => EntityManager::class,
        ],
        'delegators' => [
            EntityManager::class => [
                Doctrine\ReopeningEntityManagerDelegator::class,
            ],
        ],
    ],

];
