<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\PreviewGenerator\Image;

use mikehaertl\wkhtmlto\Image;
use Zend\ServiceManager\AbstractPluginManager;

/** @deprecated  */
class ImageBuilder extends AbstractPluginManager implements ImageBuilderInterface
{
    protected $instanceOf = Image::class;
}
