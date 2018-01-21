<?php
declare(strict_types=1);

namespace ShlinkioTest\Shlink\CLI\Command\Shortcode;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\CLI\Command\Shortcode\ListShortcodesCommand;
use Shlinkio\Shlink\Core\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Service\ShortUrlServiceInterface;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Zend\I18n\Translator\Translator;
use Zend\Paginator\Adapter\ArrayAdapter;
use Zend\Paginator\Paginator;

class ListShortcodesCommandTest extends TestCase
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
        $command = new ListShortcodesCommand($this->shortUrlService->reveal(), Translator::factory([]));
        $app->add($command);
        $this->commandTester = new CommandTester($command);
    }

    /**
     * @test
     */
    public function noInputCallsListJustOnce()
    {
        $this->shortUrlService->listShortUrls(1, null, [], null)->willReturn(new Paginator(new ArrayAdapter()))
                                                                ->shouldBeCalledTimes(1);

        $this->commandTester->setInputs(['n']);
        $this->commandTester->execute(['command' => 'shortcode:list']);
    }

    /**
     * @test
     */
    public function loadingMorePagesCallsListMoreTimes()
    {
        // The paginator will return more than one page for the first 3 times
        $data = [];
        for ($i = 0; $i < 50; $i++) {
            $data[] = new ShortUrl();
        }

        $this->shortUrlService->listShortUrls(Argument::cetera())->will(function () use (&$data) {
            return new Paginator(new ArrayAdapter($data));
        })->shouldBeCalledTimes(3);

        $this->commandTester->setInputs(['y', 'y', 'n']);
        $this->commandTester->execute(['command' => 'shortcode:list']);
    }

    /**
     * @test
     */
    public function havingMorePagesButAnsweringNoCallsListJustOnce()
    {
        // The paginator will return more than one page
        $data = [];
        for ($i = 0; $i < 30; $i++) {
            $data[] = new ShortUrl();
        }

        $this->shortUrlService->listShortUrls(Argument::cetera())->willReturn(new Paginator(new ArrayAdapter($data)))
                                                                 ->shouldBeCalledTimes(1);

        $this->commandTester->setInputs(['n']);
        $this->commandTester->execute(['command' => 'shortcode:list']);
    }

    /**
     * @test
     */
    public function passingPageWillMakeListStartOnThatPage()
    {
        $page = 5;
        $this->shortUrlService->listShortUrls($page, null, [], null)->willReturn(new Paginator(new ArrayAdapter()))
                                                                    ->shouldBeCalledTimes(1);

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
                                                                ->shouldBeCalledTimes(1);

        $this->commandTester->setInputs(['y']);
        $this->commandTester->execute([
            'command' => 'shortcode:list',
            '--showTags' => true,
        ]);
        $output = $this->commandTester->getDisplay();
        $this->assertContains('Tags', $output);
    }
}
