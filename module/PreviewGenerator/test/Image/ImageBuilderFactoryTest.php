<?php
declare(strict_types=1);

namespace ShlinkioTest\Shlink\PreviewGenerator\Image;

use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\PreviewGenerator\Image\ImageBuilder;
use Shlinkio\Shlink\PreviewGenerator\Image\ImageBuilderFactory;
use Zend\ServiceManager\ServiceManager;

class ImageBuilderFactoryTest extends TestCase
{
    /** @var ImageBuilderFactory */
    private $factory;

    public function setUp(): void
    {
        $this->factory = new ImageBuilderFactory();
    }

    /** @test */
    public function serviceIsCreated()
    {
        $instance = $this->factory->__invoke(new ServiceManager(), '');
        $this->assertInstanceOf(ImageBuilder::class, $instance);
    }
}
