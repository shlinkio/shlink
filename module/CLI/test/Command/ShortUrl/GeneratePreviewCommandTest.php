<?php
declare(strict_types=1);

namespace ShlinkioTest\Shlink\CLI\Command\ShortUrl;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\CLI\Command\ShortUrl\GeneratePreviewCommand;
use Shlinkio\Shlink\Common\Exception\PreviewGenerationException;
use Shlinkio\Shlink\Common\Service\PreviewGenerator;
use Shlinkio\Shlink\Core\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Service\ShortUrlService;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Zend\I18n\Translator\Translator;
use Zend\Paginator\Adapter\ArrayAdapter;
use Zend\Paginator\Paginator;
use function count;
use function substr_count;

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
            new ShortUrl('http://foo.com'),
            new ShortUrl('https://bar.com'),
            new ShortUrl('http://baz.com/something'),
        ]);
        $this->shortUrlService->listShortUrls(1)->willReturn($paginator)->shouldBeCalledOnce();

        $generatePreview1 = $this->previewGenerator->generatePreview('http://foo.com')->willReturn('');
        $generatePreview2 = $this->previewGenerator->generatePreview('https://bar.com')->willReturn('');
        $generatePreview3 = $this->previewGenerator->generatePreview('http://baz.com/something')->willReturn('');

        $this->commandTester->execute([
            'command' => 'shortcode:process-previews',
        ]);
        $output = $this->commandTester->getDisplay();

        $this->assertContains('Processing URL http://foo.com', $output);
        $this->assertContains('Processing URL https://bar.com', $output);
        $this->assertContains('Processing URL http://baz.com/something', $output);
        $this->assertContains('Finished processing all URLs', $output);
        $generatePreview1->shouldHaveBeenCalledOnce();
        $generatePreview2->shouldHaveBeenCalledOnce();
        $generatePreview3->shouldHaveBeenCalledOnce();
    }

    /**
     * @test
     */
    public function exceptionWillOutputError()
    {
        $items = [
            new ShortUrl('http://foo.com'),
            new ShortUrl('https://bar.com'),
            new ShortUrl('http://baz.com/something'),
        ];
        $paginator = $this->createPaginator($items);
        $this->shortUrlService->listShortUrls(1)->willReturn($paginator)->shouldBeCalledOnce();
        $this->previewGenerator->generatePreview(Argument::any())->willThrow(PreviewGenerationException::class)
                                                                 ->shouldBeCalledTimes(count($items));

        $this->commandTester->execute([
            'command' => 'shortcode:process-previews',
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
