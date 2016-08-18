<?php
namespace ShlinkioTest\Shlink\Common\Service;

use Doctrine\Common\Cache\ArrayCache;
use mikehaertl\wkhtmlto\Image;
use PHPUnit_Framework_TestCase as TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\Common\Image\ImageBuilder;
use Shlinkio\Shlink\Common\Service\PreviewGenerator;
use Zend\ServiceManager\ServiceManager;

class PreviewGeneratorTest extends TestCase
{
    /**
     * @var PreviewGenerator
     */
    protected $generator;
    /**
     * @var ObjectProphecy
     */
    protected $image;
    /**
     * @var ArrayCache
     */
    protected $cache;

    public function setUp()
    {
        $this->image = $this->prophesize(Image::class);
        $this->cache = new ArrayCache();
        $this->generator = new PreviewGenerator(new ImageBuilder(new ServiceManager(), [
            'factories' => [
                Image::class => function () {
                    return $this->image->reveal();
                },
            ]
        ]), $this->cache, 'dir');
    }

    /**
     * @test
     */
    public function alreadyCachedElementsAreNotProcessed()
    {
        $url = 'http://foo.com';
        $this->cache->save(sprintf('preview_%s.png', urlencode($url)), 'dir/foo.png');
        $this->image->saveAs(Argument::cetera())->shouldBeCalledTimes(0);
        $this->assertEquals('dir/foo.png', $this->generator->generatePreview($url));
    }

    /**
     * @test
     */
    public function nonCachedElementsAreProcessedAndThenCached()
    {
        $url = 'http://foo.com';
        $cacheId = sprintf('preview_%s.png', urlencode($url));
        $expectedPath = 'dir/' . $cacheId;

        $this->image->saveAs($expectedPath)->shouldBeCalledTimes(1);
        $this->image->getError()->willReturn('')->shouldBeCalledTimes(1);

        $this->assertFalse($this->cache->contains($cacheId));
        $this->assertEquals($expectedPath, $this->generator->generatePreview($url));
        $this->assertTrue($this->cache->contains($cacheId));
    }

    /**
     * @test
     * @expectedException \Shlinkio\Shlink\Common\Exception\PreviewGenerationException
     */
    public function errorWhileGeneratingPreviewThrowsException()
    {
        $url = 'http://foo.com';
        $cacheId = sprintf('preview_%s.png', urlencode($url));
        $expectedPath = 'dir/' . $cacheId;

        $this->image->saveAs($expectedPath)->shouldBeCalledTimes(1);
        $this->image->getError()->willReturn('Error!!')->shouldBeCalledTimes(1);

        $this->generator->generatePreview($url);
    }
}
