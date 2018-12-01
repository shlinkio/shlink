<?php
declare(strict_types=1);

use Cocur\Slugify\Slugify;
use Zend\ServiceManager\AbstractFactory\ConfigAbstractFactory;

return [

    'slugify_options' => [
        'lowercase' => false,
    ],

    'dependencies' => [
        'factories' => [
            Slugify::class => ConfigAbstractFactory::class,
        ],
    ],

    ConfigAbstractFactory::class => [
        Slugify::class => ['config.slugify_options'],
    ],

];
