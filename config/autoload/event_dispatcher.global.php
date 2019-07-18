<?php
declare(strict_types=1);

namespace Shlinkio\Shlink;

use Phly\EventDispatcher as Phly;
use Psr\EventDispatcher as Psr;

return [

    'dependencies' => [
        'factories' => [
            Psr\ListenerProviderInterface::class => Common\EventDispatcher\ListenerProviderFactory::class,
        ],
        'aliases' => [
            Psr\EventDispatcherInterface::class => Phly\EventDispatcher::class,
        ],
    ],

];
