<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\CLI\Command\ShortUrl;

use Cake\Chronos\Chronos;
use Pagerfanta\Adapter\ArrayAdapter;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\CLI\Command\ShortUrl\ListShortUrlsCommand;
use Shlinkio\Shlink\Common\Paginator\Paginator;
use Shlinkio\Shlink\Core\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Model\ShortUrlMeta;
use Shlinkio\Shlink\Core\Model\ShortUrlsParams;
use Shlinkio\Shlink\Core\Service\ShortUrlServiceInterface;
use Shlinkio\Shlink\Core\ShortUrl\Helper\ShortUrlStringifier;
use Shlinkio\Shlink\Core\ShortUrl\Transformer\ShortUrlDataTransformer;
use Shlinkio\Shlink\Rest\ApiKey\Model\ApiKeyMeta;
use Shlinkio\Shlink\Rest\Entity\ApiKey;
use ShlinkioTest\Shlink\CLI\CliTestUtilsTrait;
use Symfony\Component\Console\Tester\CommandTester;

use function count;
use function explode;

class ListShortUrlsCommandTest extends TestCase
{
    use CliTestUtilsTrait;

    private CommandTester $commandTester;
    private ObjectProphecy $shortUrlService;

    public function setUp(): void
    {
        $this->shortUrlService = $this->prophesize(ShortUrlServiceInterface::class);
        $command = new ListShortUrlsCommand($this->shortUrlService->reveal(), new ShortUrlDataTransformer(
            new ShortUrlStringifier([]),
        ));
        $this->commandTester = $this->testerForCommand($command);
    }

    /** @test */
    public function loadingMorePagesCallsListMoreTimes(): void
    {
        // The paginator will return more than one page
        $data = [];
        for ($i = 0; $i < 50; $i++) {
            $data[] = ShortUrl::withLongUrl('url_' . $i);
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
        self::assertStringNotContainsString('Continue with page 5?', $output);
    }

    /** @test */
    public function havingMorePagesButAnsweringNoCallsListJustOnce(): void
    {
        // The paginator will return more than one page
        $data = [];
        for ($i = 0; $i < 30; $i++) {
            $data[] = ShortUrl::withLongUrl('url_' . $i);
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
            ->willReturn(new Paginator(new ArrayAdapter([])))
            ->shouldBeCalledOnce();

        $this->commandTester->setInputs(['y']);
        $this->commandTester->execute(['--page' => $page]);
    }

    /**
     * @test
     * @dataProvider provideOptionalFlags
     */
    public function provideOptionalFlagsMakesNewColumnsToBeIncluded(
        array $input,
        array $expectedContents,
        array $notExpectedContents,
        ApiKey $apiKey,
    ): void {
        $this->shortUrlService->listShortUrls(ShortUrlsParams::emptyInstance())
            ->willReturn(new Paginator(new ArrayAdapter([
                ShortUrl::fromMeta(ShortUrlMeta::fromRawData([
                    'longUrl' => 'foo.com',
                    'tags' => ['foo', 'bar', 'baz'],
                    'apiKey' => $apiKey,
                ])),
            ])))
            ->shouldBeCalledOnce();

        $this->commandTester->setInputs(['y']);
        $this->commandTester->execute($input);
        $output = $this->commandTester->getDisplay();

        if (count($expectedContents) === 0 && count($notExpectedContents) === 0) {
            self::fail('No expectations were run');
        }

        foreach ($expectedContents as $column) {
            self::assertStringContainsString($column, $output);
        }
        foreach ($notExpectedContents as $column) {
            self::assertStringNotContainsString($column, $output);
        }
    }

    public function provideOptionalFlags(): iterable
    {
        $apiKey = ApiKey::fromMeta(ApiKeyMeta::withName('my api key'));
        $key = $apiKey->toString();

        yield 'tags only' => [
            ['--show-tags' => true],
            ['| Tags    ', '| foo, bar, baz'],
            ['| API Key    ', '| API Key Name |', $key, '| my api key'],
            $apiKey,
        ];
        yield 'api key only' => [
            ['--show-api-key' => true],
            ['| API Key    ', $key],
            ['| Tags    ', '| foo, bar, baz', '| API Key Name |', '| my api key'],
            $apiKey,
        ];
        yield 'api key name only' => [
            ['--show-api-key-name' => true],
            ['| API Key Name |', '| my api key'],
            ['| Tags    ', '| foo, bar, baz', '| API Key    ', $key],
            $apiKey,
        ];
        yield 'tags and api key' => [
            ['--show-tags' => true, '--show-api-key' => true],
            ['| API Key    ', '| Tags    ', '| foo, bar, baz', $key],
            ['| API Key Name |', '| my api key'],
            $apiKey,
        ];
        yield 'all' => [
            ['--show-tags' => true, '--show-api-key' => true, '--show-api-key-name' => true],
            ['| API Key    ', '| Tags    ', '| API Key Name |', '| foo, bar, baz', $key, '| my api key'],
            [],
            $apiKey,
        ];
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
        string $tagsMode,
        ?string $startDate = null,
        ?string $endDate = null,
    ): void {
        $listShortUrls = $this->shortUrlService->listShortUrls(ShortUrlsParams::fromRawData([
            'page' => $page,
            'searchTerm' => $searchTerm,
            'tags' => $tags,
            'tagsMode' => $tagsMode,
            'startDate' => $startDate !== null ? Chronos::parse($startDate)->toAtomString() : null,
            'endDate' => $endDate !== null ? Chronos::parse($endDate)->toAtomString() : null,
        ]))->willReturn(new Paginator(new ArrayAdapter([])));

        $this->commandTester->setInputs(['n']);
        $this->commandTester->execute($commandArgs);

        $listShortUrls->shouldHaveBeenCalledOnce();
    }

    public function provideArgs(): iterable
    {
        yield [[], 1, null, [], ShortUrlsParams::TAGS_MODE_ANY];
        yield [['--page' => $page = 3], $page, null, [], ShortUrlsParams::TAGS_MODE_ANY];
        yield [['--including-all-tags' => true], 1, null, [], ShortUrlsParams::TAGS_MODE_ALL];
        yield [['--search-term' => $searchTerm = 'search this'], 1, $searchTerm, [], ShortUrlsParams::TAGS_MODE_ANY];
        yield [
            ['--page' => $page = 3, '--search-term' => $searchTerm = 'search this', '--tags' => $tags = 'foo,bar'],
            $page,
            $searchTerm,
            explode(',', $tags),
            ShortUrlsParams::TAGS_MODE_ANY,
        ];
        yield [
            ['--start-date' => $startDate = '2019-01-01'],
            1,
            null,
            [],
            ShortUrlsParams::TAGS_MODE_ANY,
            $startDate,
        ];
        yield [
            ['--end-date' => $endDate = '2020-05-23'],
            1,
            null,
            [],
            ShortUrlsParams::TAGS_MODE_ANY,
            null,
            $endDate,
        ];
        yield [
            ['--start-date' => $startDate = '2019-01-01', '--end-date' => $endDate = '2020-05-23'],
            1,
            null,
            [],
            ShortUrlsParams::TAGS_MODE_ANY,
            $startDate,
            $endDate,
        ];
    }

    /**
     * @test
     * @dataProvider provideOrderBy
     */
    public function orderByIsProperlyComputed(array $commandArgs, ?string $expectedOrderBy): void
    {
        $listShortUrls = $this->shortUrlService->listShortUrls(ShortUrlsParams::fromRawData([
            'orderBy' => $expectedOrderBy,
        ]))->willReturn(new Paginator(new ArrayAdapter([])));

        $this->commandTester->setInputs(['n']);
        $this->commandTester->execute($commandArgs);

        $listShortUrls->shouldHaveBeenCalledOnce();
    }

    public function provideOrderBy(): iterable
    {
        yield [[], null];
        yield [['--order-by' => 'visits'], 'visits'];
        yield [['--order-by' => 'longUrl,ASC'], 'longUrl-ASC'];
        yield [['--order-by' => 'shortCode,DESC'], 'shortCode-DESC'];
        yield [['--order-by' => 'title-DESC'], 'title-DESC'];
    }

    /** @test */
    public function requestingAllElementsWillSetItemsPerPage(): void
    {
        $listShortUrls = $this->shortUrlService->listShortUrls(ShortUrlsParams::fromRawData([
            'page' => 1,
            'searchTerm' => null,
            'tags' => [],
            'tagsMode' => ShortUrlsParams::TAGS_MODE_ANY,
            'startDate' => null,
            'endDate' => null,
            'orderBy' => null,
            'itemsPerPage' => Paginator::ALL_ITEMS,
        ]))->willReturn(new Paginator(new ArrayAdapter([])));

        $this->commandTester->execute(['--all' => true]);

        $listShortUrls->shouldHaveBeenCalledOnce();
    }
}
