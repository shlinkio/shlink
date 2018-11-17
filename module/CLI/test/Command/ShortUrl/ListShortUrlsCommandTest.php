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
use Zend\I18n\Translator\Translator;
use Zend\Paginator\Adapter\ArrayAdapter;
use Zend\Paginator\Paginator;

class ListShortUrlsCommandTest extends TestCase
{
    /**
     * @var CommandTester
     */
    protected $commandTester;
    /**
     * @var ObjectProphecy
     */
    protected $shortUrlService;

    public function setUp()
    {
        $this->shortUrlService = $this->prophesize(ShortUrlServiceInterface::class);
        $app = new Application();
        $command = new ListShortUrlsCommand($this->shortUrlService->reveal(), Translator::factory([]), []);
        $app->add($command);
        $this->commandTester = new CommandTester($command);
    }

    /**
     * @test
     */
    public function noInputCallsListJustOnce()
    {
        $this->shortUrlService->listShortUrls(1, null, [], null)->willReturn(new Paginator(new ArrayAdapter()))
                                                                ->shouldBeCalledOnce();

        $this->commandTester->setInputs(['n']);
        $this->commandTester->execute(['command' => 'shortcode:list']);
    }

    /**
     * @test
     */
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
        $this->commandTester->execute(['command' => 'shortcode:list']);
        $output = $this->commandTester->getDisplay();

        $this->assertContains('Continue with page 2?', $output);
        $this->assertContains('Continue with page 3?', $output);
        $this->assertContains('Continue with page 4?', $output);
    }

    /**
     * @test
     */
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
        $this->commandTester->execute(['command' => 'shortcode:list']);
        $output = $this->commandTester->getDisplay();

        $this->assertContains('url_1', $output);
        $this->assertContains('url_9', $output);
        $this->assertNotContains('url_10', $output);
        $this->assertNotContains('url_20', $output);
        $this->assertNotContains('url_30', $output);
        $this->assertContains('Continue with page 2?', $output);
        $this->assertNotContains('Continue with page 3?', $output);
    }

    /**
     * @test
     */
    public function passingPageWillMakeListStartOnThatPage()
    {
        $page = 5;
        $this->shortUrlService->listShortUrls($page, null, [], null)->willReturn(new Paginator(new ArrayAdapter()))
                                                                    ->shouldBeCalledOnce();

        $this->commandTester->setInputs(['y']);
        $this->commandTester->execute([
            'command' => 'shortcode:list',
            '--page' => $page,
        ]);
    }

    /**
     * @test
     */
    public function ifTagsFlagIsProvidedTagsColumnIsIncluded()
    {
        $this->shortUrlService->listShortUrls(1, null, [], null)->willReturn(new Paginator(new ArrayAdapter()))
                                                                ->shouldBeCalledOnce();

        $this->commandTester->setInputs(['y']);
        $this->commandTester->execute([
            'command' => 'shortcode:list',
            '--showTags' => true,
        ]);
        $output = $this->commandTester->getDisplay();
        $this->assertContains('Tags', $output);
    }
}
