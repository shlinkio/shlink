<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\CLI\Command\ShortUrl;

use Cake\Chronos\Chronos;
use Laminas\Paginator\Adapter\ArrayAdapter;
use Laminas\Paginator\Paginator;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\CLI\Command\ShortUrl\ListShortUrlsCommand;
use Shlinkio\Shlink\Common\Util\DateRange;
use Shlinkio\Shlink\Core\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Service\ShortUrlServiceInterface;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

use function explode;

class ListShortUrlsCommandTest extends TestCase
{
    private CommandTester $commandTester;
    private ObjectProphecy $shortUrlService;

    public function setUp(): void
    {
        $this->shortUrlService = $this->prophesize(ShortUrlServiceInterface::class);
        $app = new Application();
        $command = new ListShortUrlsCommand($this->shortUrlService->reveal(), []);
        $app->add($command);
        $this->commandTester = new CommandTester($command);
    }

    /** @test */
    public function loadingMorePagesCallsListMoreTimes(): void
    {
        // The paginator will return more than one page
        $data = [];
        for ($i = 0; $i < 50; $i++) {
            $data[] = new ShortUrl('url_' . $i);
        }

        $this->shortUrlService->listShortUrls(Argument::cetera())
            ->will(fn () => new Paginator(new ArrayAdapter($data)))
            ->shouldBeCalledTimes(3);

        $this->commandTester->setInputs(['y', 'y', 'n']);
        $this->commandTester->execute([]);
        $output = $this->commandTester->getDisplay();

        $this->assertStringContainsString('Continue with page 2?', $output);
        $this->assertStringContainsString('Continue with page 3?', $output);
        $this->assertStringContainsString('Continue with page 4?', $output);
    }

    /** @test */
    public function havingMorePagesButAnsweringNoCallsListJustOnce(): void
    {
        // The paginator will return more than one page
        $data = [];
        for ($i = 0; $i < 30; $i++) {
            $data[] = new ShortUrl('url_' . $i);
        }

        $this->shortUrlService->listShortUrls(1, null, [], null, new DateRange())
            ->willReturn(new Paginator(new ArrayAdapter($data)))
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
    public function passingPageWillMakeListStartOnThatPage(): void
    {
        $page = 5;
        $this->shortUrlService->listShortUrls($page, null, [], null, new DateRange())
            ->willReturn(new Paginator(new ArrayAdapter()))
            ->shouldBeCalledOnce();

        $this->commandTester->setInputs(['y']);
        $this->commandTester->execute(['--page' => $page]);
    }

    /** @test */
    public function ifTagsFlagIsProvidedTagsColumnIsIncluded(): void
    {
        $this->shortUrlService->listShortUrls(1, null, [], null, new DateRange())
            ->willReturn(new Paginator(new ArrayAdapter()))
            ->shouldBeCalledOnce();

        $this->commandTester->setInputs(['y']);
        $this->commandTester->execute(['--showTags' => true]);
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Tags', $output);
    }

    /**
     * @test
     * @dataProvider provideArgs
     */
    public function serviceIsInvokedWithProvidedArgs(
        array $commandArgs,
        ?int $page,
        ?string $searchTerm,
        array $tags,
        ?DateRange $dateRange
    ): void {
        $listShortUrls = $this->shortUrlService->listShortUrls($page, $searchTerm, $tags, null, $dateRange)
            ->willReturn(new Paginator(new ArrayAdapter()));

        $this->commandTester->setInputs(['n']);
        $this->commandTester->execute($commandArgs);

        $listShortUrls->shouldHaveBeenCalledOnce();
    }

    public function provideArgs(): iterable
    {
        yield [[], 1, null, [], new DateRange()];
        yield [['--page' => $page = 3], $page, null, [], new DateRange()];
        yield [['--searchTerm' => $searchTerm = 'search this'], 1, $searchTerm, [], new DateRange()];
        yield [
            ['--page' => $page = 3, '--searchTerm' => $searchTerm = 'search this', '--tags' => $tags = 'foo,bar'],
            $page,
            $searchTerm,
            explode(',', $tags),
            new DateRange(),
        ];
        yield [
            ['--startDate' => $startDate = '2019-01-01'],
            1,
            null,
            [],
            new DateRange(Chronos::parse($startDate)),
        ];
        yield [
            ['--endDate' => $endDate = '2020-05-23'],
            1,
            null,
            [],
            new DateRange(null, Chronos::parse($endDate)),
        ];
        yield [
            ['--startDate' => $startDate = '2019-01-01', '--endDate' => $endDate = '2020-05-23'],
            1,
            null,
            [],
            new DateRange(Chronos::parse($startDate), Chronos::parse($endDate)),
        ];
    }

    /**
     * @param string|array|null $expectedOrderBy
     * @test
     * @dataProvider provideOrderBy
     */
    public function orderByIsProperlyComputed(array $commandArgs, $expectedOrderBy): void
    {
        $listShortUrls = $this->shortUrlService->listShortUrls(1, null, [], $expectedOrderBy, new DateRange())
            ->willReturn(new Paginator(new ArrayAdapter()));

        $this->commandTester->setInputs(['n']);
        $this->commandTester->execute($commandArgs);

        $listShortUrls->shouldHaveBeenCalledOnce();
    }

    public function provideOrderBy(): iterable
    {
        yield [[], null];
        yield [['--orderBy' => 'foo'], 'foo'];
        yield [['--orderBy' => 'foo,ASC'], ['foo' => 'ASC']];
        yield [['--orderBy' => 'bar,DESC'], ['bar' => 'DESC']];
    }
}
