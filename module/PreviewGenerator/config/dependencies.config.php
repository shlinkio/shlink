<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\PreviewGenerator;

use Symfony\Component\Filesystem\Filesystem;
use Zend\ServiceManager\AbstractFactory\ConfigAbstractFactory;

return [

    'dependencies' => [
        'factories' => [
            Image\ImageBuilder::class => Image\ImageBuilderFactory::class,
            Service\PreviewGenerator::class => ConfigAbstractFactory::class,
        ],
    ],

    ConfigAbstractFactory::class => [
        Service\PreviewGenerator::class => [
            Image\ImageBuilder::class,
            Filesystem::class,
            'config.preview_generation.files_location',
        ],
    ],

];
