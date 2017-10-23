<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Common\Image;

use mikehaertl\wkhtmlto\Image;
use Zend\ServiceManager\AbstractPluginManager;

class ImageBuilder extends AbstractPluginManager implements ImageBuilderInterface
{
    protected $instanceOf = Image::class;
}
