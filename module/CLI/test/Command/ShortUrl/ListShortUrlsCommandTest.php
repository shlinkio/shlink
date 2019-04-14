<?php
declare(strict_types=1);

namespace ShlinkioTest\Shlink\CLI\Command\ShortUrl;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\CLI\Command\ShortUrl\ListShortUrlsCommand;
use Shlinkio\Shlink\Core\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Service\ShortUrlServiceInterface;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Zend\Paginator\Adapter\ArrayAdapter;
use Zend\Paginator\Paginator;

class ListShortUrlsCommandTest extends TestCase
{
    /** @var CommandTester */
    private $commandTester;
    /** @var ObjectProphecy */
    private $shortUrlService;

    public function setUp(): void
    {
        $this->shortUrlService = $this->prophesize(ShortUrlServiceInterface::class);
        $app = new Application();
        $command = new ListShortUrlsCommand($this->shortUrlService->reveal(), []);
        $app->add($command);
        $this->commandTester = new CommandTester($command);
    }

    /** @test */
    public function noInputCallsListJustOnce()
    {
        $this->shortUrlService->listShortUrls(1, null, [], null)->willReturn(new Paginator(new ArrayAdapter()))
                                                                ->shouldBeCalledOnce();

        $this->commandTester->setInputs(['n']);
        $this->commandTester->execute([]);
    }

    /** @test */
    public function loadingMorePagesCallsListMoreTimes()
    {
        // The paginator will return more than one page
        $data = [];
        for ($i = 0; $i < 50; $i++) {
            $data[] = new ShortUrl('url_' . $i);
        }

        $this->shortUrlService->listShortUrls(Argument::cetera())->will(function () use (&$data) {
            return new Paginator(new ArrayAdapter($data));
        })->shouldBeCalledTimes(3);

        $this->commandTester->setInputs(['y', 'y', 'n']);
        $this->commandTester->execute([]);
        $output = $this->commandTester->getDisplay();

        $this->assertStringContainsString('Continue with page 2?', $output);
        $this->assertStringContainsString('Continue with page 3?', $output);
        $this->assertStringContainsString('Continue with page 4?', $output);
    }

    /** @test */
    public function havingMorePagesButAnsweringNoCallsListJustOnce()
    {
        // The paginator will return more than one page
        $data = [];
        for ($i = 0; $i < 30; $i++) {
            $data[] = new ShortUrl('url_' . $i);
        }

        $this->shortUrlService->listShortUrls(1, null, [], null)->willReturn(new Paginator(new ArrayAdapter($data)))
                                                                ->shouldBeCalledOnce();

        $this->commandTester->setInputs(['n']);
        $this->commandTester->execute([]);
        $output = $this->commandTester->getDisplay();

        $this->assertStringContainsString('url_1', $output);
        $this->assertStringContainsString('url_9', $output);
        $this->assertStringNotContainsString('url_10', $output);
        $this->assertStringNotContainsString('url_20', $output);
        $this->assertStringNotContainsString('url_30', $output);
        $this->assertStringContainsString('Continue with page 2?', $output);
        $this->assertStringNotContainsString('Continue with page 3?', $output);
    }

    /** @test */
    public function passingPageWillMakeListStartOnThatPage()
    {
        $page = 5;
        $this->shortUrlService->listShortUrls($page, null, [], null)->willReturn(new Paginator(new ArrayAdapter()))
                                                                    ->shouldBeCalledOnce();

        $this->commandTester->setInputs(['y']);
        $this->commandTester->execute(['--page' => $page]);
    }

    /** @test */
    public function ifTagsFlagIsProvidedTagsColumnIsIncluded()
    {
        $this->shortUrlService->listShortUrls(1, null, [], null)->willReturn(new Paginator(new ArrayAdapter()))
                                                                ->shouldBeCalledOnce();

        $this->commandTester->setInputs(['y']);
        $this->commandTester->execute(['--showTags' => true]);
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Tags', $output);
    }
}
