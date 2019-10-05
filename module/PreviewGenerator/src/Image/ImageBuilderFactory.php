<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\PreviewGenerator\Image;

use mikehaertl\wkhtmlto\Image;
use Psr\Container\ContainerInterface;

/** @deprecated  */
class ImageBuilderFactory
{
    public function __invoke(ContainerInterface $container)
    {
        return new ImageBuilder($container, ['factories' => [
            Image::class => ImageFactory::class,
        ]]);
    }
}
