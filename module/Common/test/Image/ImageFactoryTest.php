<?php
declare(strict_types=1);

namespace ShlinkioTest\Shlink\Common\Image;

use mikehaertl\wkhtmlto\Image;
use PHPUnit\Framework\TestCase;
use ReflectionObject;
use Shlinkio\Shlink\Common\Image\ImageFactory;
use Zend\ServiceManager\ServiceManager;

class ImageFactoryTest extends TestCase
{
    /** @var ImageFactory */
    protected $factory;

    public function setUp()
    {
        $this->factory = new ImageFactory();
    }

    /**
     * @test
     */
    public function noPageIsSetWhenOptionsAreNotProvided()
    {
        /** @var Image $image */
        $image = $this->factory->__invoke(new ServiceManager(['services' => [
            'config' => ['phpwkhtmltopdf' => []],
        ]]), '');
        $this->assertInstanceOf(Image::class, $image);

        $ref = new ReflectionObject($image);
        $page = $ref->getProperty('_page');
        $page->setAccessible(true);
        $this->assertNull($page->getValue($image));
    }

    /**
     * @test
     */
    public function aPageIsSetWhenOptionsIncludeTheUrl()
    {
        $expectedPage = 'foo/bar.html';

        /** @var Image $image */
        $image = $this->factory->__invoke(new ServiceManager(['services' => [
            'config' => ['phpwkhtmltopdf' => []],
        ]]), '', ['url' => $expectedPage]);
        $this->assertInstanceOf(Image::class, $image);

        $ref = new ReflectionObject($image);
        $page = $ref->getProperty('_page');
        $page->setAccessible(true);
        $this->assertEquals($expectedPage, $page->getValue($image));
    }
}
