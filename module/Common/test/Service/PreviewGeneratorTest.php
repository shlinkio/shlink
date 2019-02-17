<?php
declare(strict_types=1);

namespace ShlinkioTest\Shlink\Common\Service;

use mikehaertl\wkhtmlto\Image;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\Common\Exception\PreviewGenerationException;
use Shlinkio\Shlink\Common\Image\ImageBuilder;
use Shlinkio\Shlink\Common\Service\PreviewGenerator;
use Symfony\Component\Filesystem\Filesystem;
use Zend\ServiceManager\ServiceManager;
use function sprintf;
use function urlencode;

class PreviewGeneratorTest extends TestCase
{
    /** @var PreviewGenerator */
    private $generator;
    /** @var ObjectProphecy */
    private $image;
    /** @var ObjectProphecy */
    private $filesystem;

    public function setUp(): void
    {
        $this->image = $this->prophesize(Image::class);
        $this->filesystem = $this->prophesize(Filesystem::class);

        $this->generator = new PreviewGenerator(new ImageBuilder(new ServiceManager(), [
            'factories' => [
                Image::class => function () {
                    return $this->image->reveal();
                },
            ],
        ]), $this->filesystem->reveal(), 'dir');
    }

    /** @test */
    public function alreadyProcessedElementsAreNotProcessed()
    {
        $url = 'http://foo.com';
        $this->filesystem->exists(sprintf('dir/preview_%s.png', urlencode($url)))->willReturn(true)
                                                                                 ->shouldBeCalledOnce();
        $this->image->saveAs(Argument::cetera())->shouldBeCalledTimes(0);
        $this->assertEquals(sprintf('dir/preview_%s.png', urlencode($url)), $this->generator->generatePreview($url));
    }

    /** @test */
    public function nonProcessedElementsAreProcessed()
    {
        $url = 'http://foo.com';
        $cacheId = sprintf('preview_%s.png', urlencode($url));
        $expectedPath = 'dir/' . $cacheId;

        $this->filesystem->exists(sprintf('dir/preview_%s.png', urlencode($url)))->willReturn(false)
                                                                                 ->shouldBeCalledOnce();

        $this->image->saveAs($expectedPath)->shouldBeCalledOnce();
        $this->image->getError()->willReturn('')->shouldBeCalledOnce();
        $this->assertEquals($expectedPath, $this->generator->generatePreview($url));
    }

    /** @test */
    public function errorWhileGeneratingPreviewThrowsException()
    {
        $url = 'http://foo.com';
        $cacheId = sprintf('preview_%s.png', urlencode($url));
        $expectedPath = 'dir/' . $cacheId;

        $this->filesystem->exists(sprintf('dir/preview_%s.png', urlencode($url)))->willReturn(false)
                                                                                 ->shouldBeCalledOnce();

        $this->image->saveAs($expectedPath)->shouldBeCalledOnce();
        $this->image->getError()->willReturn('Error!!')->shouldBeCalledOnce();

        $this->expectException(PreviewGenerationException::class);

        $this->generator->generatePreview($url);
    }
}
