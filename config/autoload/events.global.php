<?php
declare(strict_types=1);

namespace Shlinkio\Shlink;

use Phly\EventDispatcher as Phly;
use Psr\EventDispatcher as Psr;
use Zend\ServiceManager\AbstractFactory\ConfigAbstractFactory;

return [

    'dependencies' => [
        'factories' => [
            Common\EventDispatcher\SwooleEventDispatcher::class => ConfigAbstractFactory::class,
            Psr\ListenerProviderInterface::class => Common\EventDispatcher\ListenerProviderFactory::class,
        ],
        'aliases' => [
            Psr\EventDispatcherInterface::class => Common\EventDispatcher\SwooleEventDispatcher::class,
        ],
    ],

    ConfigAbstractFactory::class => [
        Common\EventDispatcher\SwooleEventDispatcher::class => [Phly\EventDispatcher::class],
    ],

];
