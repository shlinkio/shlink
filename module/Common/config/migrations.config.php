<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Common;

use Doctrine\ORM\EntityManager;

return [

    'dependencies' => [
        'delegators' => [
            EntityManager::class => [
                Migrations\LockMigrationsEntityManagerDelegator::class,
            ],
        ],
    ],

];
