<?php
namespace ShlinkioTest\Shlink\CLI\Command\Shortcode;

use PHPUnit_Framework_TestCase as TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\CLI\Command\Shortcode\GeneratePreviewCommand;
use Shlinkio\Shlink\Common\Exception\PreviewGenerationException;
use Shlinkio\Shlink\Common\Service\PreviewGenerator;
use Shlinkio\Shlink\Core\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Service\ShortUrlService;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Zend\I18n\Translator\Translator;
use Zend\Paginator\Adapter\ArrayAdapter;
use Zend\Paginator\Paginator;

class GeneratePreviewCommandTest extends TestCase
{
    /**
     * @var CommandTester
     */
    protected $commandTester;
    /**
     * @var ObjectProphecy
     */
    private $previewGenerator;
    /**
     * @var ObjectProphecy
     */
    private $shortUrlService;

    public function setUp()
    {
        $this->previewGenerator = $this->prophesize(PreviewGenerator::class);
        $this->shortUrlService = $this->prophesize(ShortUrlService::class);

        $command = new GeneratePreviewCommand(
            $this->shortUrlService->reveal(),
            $this->previewGenerator->reveal(),
            Translator::factory([])
        );
        $app = new Application();
        $app->add($command);

        $this->commandTester = new CommandTester($command);
    }

    /**
     * @test
     */
    public function previewsForEveryUrlAreGenerated()
    {
        $paginator = $this->createPaginator([
            (new ShortUrl())->setOriginalUrl('http://foo.com'),
            (new ShortUrl())->setOriginalUrl('https://bar.com'),
            (new ShortUrl())->setOriginalUrl('http://baz.com/something'),
        ]);
        $this->shortUrlService->listShortUrls(1)->willReturn($paginator)->shouldBeCalledTimes(1);

        $this->previewGenerator->generatePreview('http://foo.com')->shouldBeCalledTimes(1);
        $this->previewGenerator->generatePreview('https://bar.com')->shouldBeCalledTimes(1);
        $this->previewGenerator->generatePreview('http://baz.com/something')->shouldBeCalledTimes(1);

        $this->commandTester->execute([
            'command' => 'shortcode:process-previews'
        ]);
    }

    /**
     * @test
     */
    public function exceptionWillOutputError()
    {
        $items = [
            (new ShortUrl())->setOriginalUrl('http://foo.com'),
            (new ShortUrl())->setOriginalUrl('https://bar.com'),
            (new ShortUrl())->setOriginalUrl('http://baz.com/something'),
        ];
        $paginator = $this->createPaginator($items);
        $this->shortUrlService->listShortUrls(1)->willReturn($paginator)->shouldBeCalledTimes(1);
        $this->previewGenerator->generatePreview(Argument::any())->willThrow(PreviewGenerationException::class)
                                                                 ->shouldBeCalledTimes(count($items));

        $this->commandTester->execute([
            'command' => 'shortcode:process-previews'
        ]);
        $output = $this->commandTester->getDisplay();
        $this->assertEquals(count($items), substr_count($output, 'Error'));
    }

    protected function createPaginator(array $items)
    {
        $paginator = new Paginator(new ArrayAdapter($items));
        $paginator->setItemCountPerPage(count($items));

        return $paginator;
    }
}
