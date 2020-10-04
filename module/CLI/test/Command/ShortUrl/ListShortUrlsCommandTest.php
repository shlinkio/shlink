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
use Shlinkio\Shlink\Core\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Model\ShortUrlsParams;
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

        self::assertStringContainsString('Continue with page 2?', $output);
        self::assertStringContainsString('Continue with page 3?', $output);
        self::assertStringContainsString('Continue with page 4?', $output);
    }

    /** @test */
    public function havingMorePagesButAnsweringNoCallsListJustOnce(): void
    {
        // The paginator will return more than one page
        $data = [];
        for ($i = 0; $i < 30; $i++) {
            $data[] = new ShortUrl('url_' . $i);
        }

        $this->shortUrlService->listShortUrls(ShortUrlsParams::emptyInstance())
            ->willReturn(new Paginator(new ArrayAdapter($data)))
            ->shouldBeCalledOnce();

        $this->commandTester->setInputs(['n']);
        $this->commandTester->execute([]);
        $output = $this->commandTester->getDisplay();

        self::assertStringContainsString('url_1', $output);
        self::assertStringContainsString('url_9', $output);
        self::assertStringNotContainsString('url_10', $output);
        self::assertStringNotContainsString('url_20', $output);
        self::assertStringNotContainsString('url_30', $output);
        self::assertStringContainsString('Continue with page 2?', $output);
        self::assertStringNotContainsString('Continue with page 3?', $output);
    }

    /** @test */
    public function passingPageWillMakeListStartOnThatPage(): void
    {
        $page = 5;
        $this->shortUrlService->listShortUrls(ShortUrlsParams::fromRawData(['page' => $page]))
            ->willReturn(new Paginator(new ArrayAdapter()))
            ->shouldBeCalledOnce();

        $this->commandTester->setInputs(['y']);
        $this->commandTester->execute(['--page' => $page]);
    }

    /** @test */
    public function ifTagsFlagIsProvidedTagsColumnIsIncluded(): void
    {
        $this->shortUrlService->listShortUrls(ShortUrlsParams::emptyInstance())
            ->willReturn(new Paginator(new ArrayAdapter()))
            ->shouldBeCalledOnce();

        $this->commandTester->setInputs(['y']);
        $this->commandTester->execute(['--showTags' => true]);
        $output = $this->commandTester->getDisplay();
        self::assertStringContainsString('Tags', $output);
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
        ?string $startDate = null,
        ?string $endDate = null
    ): void {
        $listShortUrls = $this->shortUrlService->listShortUrls(ShortUrlsParams::fromRawData([
            'page' => $page,
            'searchTerm' => $searchTerm,
            'tags' => $tags,
            'startDate' => $startDate !== null ? Chronos::parse($startDate)->toAtomString() : null,
            'endDate' => $endDate !== null ? Chronos::parse($endDate)->toAtomString() : null,
        ]))->willReturn(new Paginator(new ArrayAdapter()));

        $this->commandTester->setInputs(['n']);
        $this->commandTester->execute($commandArgs);

        $listShortUrls->shouldHaveBeenCalledOnce();
    }

    public function provideArgs(): iterable
    {
        yield [[], 1, null, []];
        yield [['--page' => $page = 3], $page, null, []];
        yield [['--searchTerm' => $searchTerm = 'search this'], 1, $searchTerm, []];
        yield [
            ['--page' => $page = 3, '--searchTerm' => $searchTerm = 'search this', '--tags' => $tags = 'foo,bar'],
            $page,
            $searchTerm,
            explode(',', $tags),
        ];
        yield [
            ['--startDate' => $startDate = '2019-01-01'],
            1,
            null,
            [],
            $startDate,
        ];
        yield [
            ['--endDate' => $endDate = '2020-05-23'],
            1,
            null,
            [],
            null,
            $endDate,
        ];
        yield [
            ['--startDate' => $startDate = '2019-01-01', '--endDate' => $endDate = '2020-05-23'],
            1,
            null,
            [],
            $startDate,
            $endDate,
        ];
    }

    /**
     * @param string|array|null $expectedOrderBy
     * @test
     * @dataProvider provideOrderBy
     */
    public function orderByIsProperlyComputed(array $commandArgs, $expectedOrderBy): void
    {
        $listShortUrls = $this->shortUrlService->listShortUrls(ShortUrlsParams::fromRawData([
            'orderBy' => $expectedOrderBy,
        ]))->willReturn(new Paginator(new ArrayAdapter()));

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

    /** @test */
    public function requestingAllElementsWillSetItemsPerPage(): void
    {
        $listShortUrls = $this->shortUrlService->listShortUrls(ShortUrlsParams::fromRawData([
            'page' => 1,
            'searchTerm' => null,
            'tags' => [],
            'startDate' => null,
            'endDate' => null,
            'orderBy' => null,
            'itemsPerPage' => -1,
        ]))->willReturn(new Paginator(new ArrayAdapter()));

        $this->commandTester->execute(['--all' => true]);

        $listShortUrls->shouldHaveBeenCalledOnce();
    }
}
