<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\CLI\Command\ShortUrl;

use Cake\Chronos\Chronos;
use Pagerfanta\Adapter\ArrayAdapter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\CLI\Command\ShortUrl\ListShortUrlsCommand;
use Shlinkio\Shlink\Common\Paginator\Paginator;
use Shlinkio\Shlink\Core\ShortUrl\Entity\ShortUrl;
use Shlinkio\Shlink\Core\ShortUrl\Helper\ShortUrlStringifier;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlCreation;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlsParams;
use Shlinkio\Shlink\Core\ShortUrl\Model\TagsMode;
use Shlinkio\Shlink\Core\ShortUrl\ShortUrlListServiceInterface;
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
    private MockObject & ShortUrlListServiceInterface $shortUrlService;

    protected function setUp(): void
    {
        $this->shortUrlService = $this->createMock(ShortUrlListServiceInterface::class);
        $command = new ListShortUrlsCommand($this->shortUrlService, new ShortUrlDataTransformer(
            new ShortUrlStringifier([]),
        ));
        $this->commandTester = $this->testerForCommand($command);
    }

    #[Test]
    public function loadingMorePagesCallsListMoreTimes(): void
    {
        // The paginator will return more than one page
        $data = [];
        for ($i = 0; $i < 50; $i++) {
            $data[] = ShortUrl::withLongUrl('url_' . $i);
        }

        $this->shortUrlService->expects($this->exactly(3))->method('listShortUrls')->withAnyParameters()
            ->willReturnCallback(fn () => new Paginator(new ArrayAdapter($data)));

        $this->commandTester->setInputs(['y', 'y', 'n']);
        $this->commandTester->execute([]);
        $output = $this->commandTester->getDisplay();

        self::assertStringContainsString('Continue with page 2?', $output);
        self::assertStringContainsString('Continue with page 3?', $output);
        self::assertStringContainsString('Continue with page 4?', $output);
        self::assertStringNotContainsString('Continue with page 5?', $output);
    }

    #[Test]
    public function havingMorePagesButAnsweringNoCallsListJustOnce(): void
    {
        // The paginator will return more than one page
        $data = [];
        for ($i = 0; $i < 30; $i++) {
            $data[] = ShortUrl::withLongUrl('url_' . $i);
        }

        $this->shortUrlService->expects($this->once())->method('listShortUrls')->with(
            ShortUrlsParams::emptyInstance(),
        )->willReturn(new Paginator(new ArrayAdapter($data)));

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

    #[Test]
    public function passingPageWillMakeListStartOnThatPage(): void
    {
        $page = 5;
        $this->shortUrlService->expects($this->once())->method('listShortUrls')->with(
            ShortUrlsParams::fromRawData(['page' => $page]),
        )->willReturn(new Paginator(new ArrayAdapter([])));

        $this->commandTester->setInputs(['y']);
        $this->commandTester->execute(['--page' => $page]);
    }

    #[Test, DataProvider('provideOptionalFlags')]
    public function provideOptionalFlagsMakesNewColumnsToBeIncluded(
        array $input,
        array $expectedContents,
        array $notExpectedContents,
        ApiKey $apiKey,
    ): void {
        $this->shortUrlService->expects($this->once())->method('listShortUrls')->with(
            ShortUrlsParams::emptyInstance(),
        )->willReturn(new Paginator(new ArrayAdapter([
            ShortUrl::create(ShortUrlCreation::fromRawData([
                'longUrl' => 'foo.com',
                'tags' => ['foo', 'bar', 'baz'],
                'apiKey' => $apiKey,
            ])),
        ])));

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

    public static function provideOptionalFlags(): iterable
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

    #[Test, DataProvider('provideArgs')]
    public function serviceIsInvokedWithProvidedArgs(
        array $commandArgs,
        ?int $page,
        ?string $searchTerm,
        array $tags,
        string $tagsMode,
        ?string $startDate = null,
        ?string $endDate = null,
    ): void {
        $this->shortUrlService->expects($this->once())->method('listShortUrls')->with(ShortUrlsParams::fromRawData([
            'page' => $page,
            'searchTerm' => $searchTerm,
            'tags' => $tags,
            'tagsMode' => $tagsMode,
            'startDate' => $startDate !== null ? Chronos::parse($startDate)->toAtomString() : null,
            'endDate' => $endDate !== null ? Chronos::parse($endDate)->toAtomString() : null,
        ]))->willReturn(new Paginator(new ArrayAdapter([])));

        $this->commandTester->setInputs(['n']);
        $this->commandTester->execute($commandArgs);
    }

    public static function provideArgs(): iterable
    {
        yield [[], 1, null, [], TagsMode::ANY->value];
        yield [['--page' => $page = 3], $page, null, [], TagsMode::ANY->value];
        yield [['--including-all-tags' => true], 1, null, [], TagsMode::ALL->value];
        yield [['--search-term' => $searchTerm = 'search this'], 1, $searchTerm, [], TagsMode::ANY->value];
        yield [
            ['--page' => $page = 3, '--search-term' => $searchTerm = 'search this', '--tags' => $tags = 'foo,bar'],
            $page,
            $searchTerm,
            explode(',', $tags),
            TagsMode::ANY->value,
        ];
        yield [
            ['--start-date' => $startDate = '2019-01-01'],
            1,
            null,
            [],
            TagsMode::ANY->value,
            $startDate,
        ];
        yield [
            ['--end-date' => $endDate = '2020-05-23'],
            1,
            null,
            [],
            TagsMode::ANY->value,
            null,
            $endDate,
        ];
        yield [
            ['--start-date' => $startDate = '2019-01-01', '--end-date' => $endDate = '2020-05-23'],
            1,
            null,
            [],
            TagsMode::ANY->value,
            $startDate,
            $endDate,
        ];
    }

    #[Test, DataProvider('provideOrderBy')]
    public function orderByIsProperlyComputed(array $commandArgs, ?string $expectedOrderBy): void
    {
        $this->shortUrlService->expects($this->once())->method('listShortUrls')->with(ShortUrlsParams::fromRawData([
            'orderBy' => $expectedOrderBy,
        ]))->willReturn(new Paginator(new ArrayAdapter([])));

        $this->commandTester->setInputs(['n']);
        $this->commandTester->execute($commandArgs);
    }

    public static function provideOrderBy(): iterable
    {
        yield [[], null];
        yield [['--order-by' => 'visits'], 'visits'];
        yield [['--order-by' => 'longUrl,ASC'], 'longUrl-ASC'];
        yield [['--order-by' => 'shortCode,DESC'], 'shortCode-DESC'];
        yield [['--order-by' => 'title-DESC'], 'title-DESC'];
    }

    #[Test]
    public function requestingAllElementsWillSetItemsPerPage(): void
    {
        $this->shortUrlService->expects($this->once())->method('listShortUrls')->with(ShortUrlsParams::fromRawData([
            'page' => 1,
            'searchTerm' => null,
            'tags' => [],
            'tagsMode' => TagsMode::ANY->value,
            'startDate' => null,
            'endDate' => null,
            'orderBy' => null,
            'itemsPerPage' => Paginator::ALL_ITEMS,
        ]))->willReturn(new Paginator(new ArrayAdapter([])));

        $this->commandTester->execute(['--all' => true]);
    }
}
