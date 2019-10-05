<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\CLI\Command\ShortUrl;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\CLI\Command\ShortUrl\GeneratePreviewCommand;
use Shlinkio\Shlink\Core\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Service\ShortUrlService;
use Shlinkio\Shlink\PreviewGenerator\Exception\PreviewGenerationException;
use Shlinkio\Shlink\PreviewGenerator\Service\PreviewGenerator;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Zend\Paginator\Adapter\ArrayAdapter;
use Zend\Paginator\Paginator;

use function count;
use function substr_count;

class GeneratePreviewCommandTest extends TestCase
{
    /** @var CommandTester */
    private $commandTester;
    /** @var ObjectProphecy */
    private $previewGenerator;
    /** @var ObjectProphecy */
    private $shortUrlService;

    public function setUp(): void
    {
        $this->previewGenerator = $this->prophesize(PreviewGenerator::class);
        $this->shortUrlService = $this->prophesize(ShortUrlService::class);

        $command = new GeneratePreviewCommand($this->shortUrlService->reveal(), $this->previewGenerator->reveal());
        $app = new Application();
        $app->add($command);

        $this->commandTester = new CommandTester($command);
    }

    /** @test */
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

        $this->commandTester->execute([]);
        $output = $this->commandTester->getDisplay();

        $this->assertStringContainsString('Processing URL http://foo.com', $output);
        $this->assertStringContainsString('Processing URL https://bar.com', $output);
        $this->assertStringContainsString('Processing URL http://baz.com/something', $output);
        $this->assertStringContainsString('Finished processing all URLs', $output);
        $generatePreview1->shouldHaveBeenCalledOnce();
        $generatePreview2->shouldHaveBeenCalledOnce();
        $generatePreview3->shouldHaveBeenCalledOnce();
    }

    /** @test */
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

        $this->commandTester->execute([]);
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
