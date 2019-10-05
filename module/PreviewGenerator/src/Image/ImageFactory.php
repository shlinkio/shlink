<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\PreviewGenerator\Image;

use mikehaertl\wkhtmlto\Image;
use Psr\Container\ContainerInterface;

/** @deprecated  */
class ImageFactory
{
    public function __invoke(ContainerInterface $container, string $requestedName, ?array $options = null)
    {
        $config = $container->get('config')['wkhtmltopdf'];
        $image = new Image($config['images'] ?? null);

        if ($options['url'] ?? null) {
            $image->setPage($options['url']);
        }

        return $image;
    }
}
