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
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlWithDeps;
use Shlinkio\Shlink\Core\ShortUrl\Model\TagsMode;
use Shlinkio\Shlink\Core\ShortUrl\ShortUrlListServiceInterface;
use Shlinkio\Shlink\Core\ShortUrl\Transformer\ShortUrlDataTransformer;
use Shlinkio\Shlink\Rest\ApiKey\Model\ApiKeyMeta;
use Shlinkio\Shlink\Rest\Entity\ApiKey;
use ShlinkioTest\Shlink\CLI\Util\CliTestUtils;
use Symfony\Component\Console\Tester\CommandTester;

class ListShortUrlsCommandTest extends TestCase
{
    private CommandTester $commandTester;
    private MockObject & ShortUrlListServiceInterface $shortUrlService;

    protected function setUp(): void
    {
        $this->shortUrlService = $this->createMock(ShortUrlListServiceInterface::class);
        $command = new ListShortUrlsCommand($this->shortUrlService, new ShortUrlDataTransformer(
            new ShortUrlStringifier(),
        ));
        $this->commandTester = CliTestUtils::testerForCommand($command);
    }

    #[Test]
    public function loadingMorePagesCallsListMoreTimes(): void
    {
        // The paginator will return more than one page
        $data = [];
        for ($i = 0; $i < 50; $i++) {
            $data[] = ShortUrlWithDeps::fromShortUrl(ShortUrl::withLongUrl('https://url_' . $i));
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
            $data[] = ShortUrlWithDeps::fromShortUrl(ShortUrl::withLongUrl('https://url_' . $i));
        }

        $this->shortUrlService->expects($this->once())->method('listShortUrls')->with(
            ShortUrlsParams::empty(),
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
        string $expectedOutput,
        ShortUrl $shortUrl,
    ): void {
        $this->shortUrlService->expects($this->once())->method('listShortUrls')->with(
            ShortUrlsParams::empty(),
        )->willReturn(new Paginator(new ArrayAdapter([
            ShortUrlWithDeps::fromShortUrl($shortUrl),
        ])));

        $this->commandTester->setInputs(['y']);
        $this->commandTester->execute($input);
        $output = $this->commandTester->getDisplay();

        self::assertStringContainsString($expectedOutput, $output);
    }

    public static function provideOptionalFlags(): iterable
    {
        $shortUrl = ShortUrl::create(ShortUrlCreation::fromRawData([
            'longUrl' => 'https://foo.com',
            'tags' => ['foo', 'bar', 'baz'],
            'apiKey' => ApiKey::fromMeta(ApiKeyMeta::fromParams(name: 'my api key')),
        ]));
        $shortCode = $shortUrl->getShortCode();
        $created = $shortUrl->dateCreated()->toAtomString();

        // phpcs:disable Generic.Files.LineLength
        yield 'tags only' => [
            ['--show-tags' => true],
            <<<OUTPUT
            +------------+-------+-------------+-----------------+---------------------------+--------------+---------------+
            | Short Code | Title | Short URL   | Long URL        | Date created              | Visits count | Tags          |
            +------------+-------+-------------+-----------------+---------------------------+--------------+---------------+
            | {$shortCode}      |       | http:/{$shortCode} | https://foo.com | {$created} | 0            | foo, bar, baz |
            +------------+-------+-------------+-------------- Page 1 of 1 ------------------+--------------+---------------+
            OUTPUT,
            $shortUrl,
        ];
        yield 'domain only' => [
            ['--show-domain' => true],
            <<<OUTPUT
            +------------+-------+-------------+-----------------+---------------------------+--------------+---------+
            | Short Code | Title | Short URL   | Long URL        | Date created              | Visits count | Domain  |
            +------------+-------+-------------+-----------------+---------------------------+--------------+---------+
            | {$shortCode}      |       | http:/{$shortCode} | https://foo.com | {$created} | 0            | DEFAULT |
            +------------+-------+-------------+----------- Page 1 of 1 ---------------------+--------------+---------+
            OUTPUT,
            $shortUrl,
        ];
        yield 'api key only' => [
            ['--show-api-key' => true],
            <<<OUTPUT
            +------------+-------+-------------+-----------------+---------------------------+--------------+--------------+
            | Short Code | Title | Short URL   | Long URL        | Date created              | Visits count | API Key Name |
            +------------+-------+-------------+-----------------+---------------------------+--------------+--------------+
            | {$shortCode}      |       | http:/{$shortCode} | https://foo.com | {$created} | 0            | my api key   |
            +------------+-------+-------------+------------- Page 1 of 1 -------------------+--------------+--------------+
            OUTPUT,
            $shortUrl,
        ];
        yield 'tags and api key' => [
            ['--show-tags' => true, '--show-api-key' => true],
            <<<OUTPUT
            +------------+-------+-------------+-----------------+---------------------------+--------------+---------------+--------------+
            | Short Code | Title | Short URL   | Long URL        | Date created              | Visits count | Tags          | API Key Name |
            +------------+-------+-------------+-----------------+---------------------------+--------------+---------------+--------------+
            | {$shortCode}      |       | http:/{$shortCode} | https://foo.com | {$created} | 0            | foo, bar, baz | my api key   |
            +------------+-------+-------------+-----------------+--- Page 1 of 1 -----------+--------------+---------------+--------------+
            OUTPUT,
            $shortUrl,
        ];
        yield 'tags and domain' => [
            ['--show-tags' => true, '--show-domain' => true],
            <<<OUTPUT
            +------------+-------+-------------+-----------------+---------------------------+--------------+---------------+---------+
            | Short Code | Title | Short URL   | Long URL        | Date created              | Visits count | Tags          | Domain  |
            +------------+-------+-------------+-----------------+---------------------------+--------------+---------------+---------+
            | {$shortCode}      |       | http:/{$shortCode} | https://foo.com | {$created} | 0            | foo, bar, baz | DEFAULT |
            +------------+-------+-------------+-----------------+- Page 1 of 1 -------------+--------------+---------------+---------+
            OUTPUT,
            $shortUrl,
        ];
        yield 'all' => [
            ['--show-tags' => true, '--show-domain' => true, '--show-api-key' => true],
            <<<OUTPUT
            +------------+-------+-------------+-----------------+---------------------------+--------------+---------------+---------+--------------+
            | Short Code | Title | Short URL   | Long URL        | Date created              | Visits count | Tags          | Domain  | API Key Name |
            +------------+-------+-------------+-----------------+---------------------------+--------------+---------------+---------+--------------+
            | {$shortCode}      |       | http:/{$shortCode} | https://foo.com | {$created} | 0            | foo, bar, baz | DEFAULT | my api key   |
            +------------+-------+-------------+-----------------+-------- Page 1 of 1 ------+--------------+---------------+---------+--------------+
            OUTPUT,
            $shortUrl,
        ];
        // phpcs:enable
    }

    #[Test, DataProvider('provideArgs')]
    public function serviceIsInvokedWithProvidedArgs(
        array $commandArgs,
        int|null $page,
        string|null $searchTerm,
        array $tags,
        string $tagsMode,
        string|null $startDate = null,
        string|null $endDate = null,
        array $excludeTags = [],
        string $excludeTagsMode = TagsMode::ANY->value,
    ): void {
        $this->shortUrlService->expects($this->once())->method('listShortUrls')->with(ShortUrlsParams::fromRawData([
            'page' => $page,
            'searchTerm' => $searchTerm,
            'tags' => $tags,
            'tagsMode' => $tagsMode,
            'startDate' => $startDate !== null ? Chronos::parse($startDate)->toAtomString() : null,
            'endDate' => $endDate !== null ? Chronos::parse($endDate)->toAtomString() : null,
            'excludeTags' => $excludeTags,
            'excludeTagsMode' => $excludeTagsMode,
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
            ['--page' => $page = 3, '--search-term' => $searchTerm = 'search this', '--tag' => $tags = ['foo', 'bar']],
            $page,
            $searchTerm,
            $tags,
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
        yield [
            ['--exclude-tag' => ['foo', 'bar'], '--exclude-tags-all' => true],
            1,
            null,
            [],
            TagsMode::ANY->value,
            null,
            null,
            ['foo', 'bar'],
            TagsMode::ALL->value,
        ];
    }

    #[Test, DataProvider('provideOrderBy')]
    public function orderByIsProperlyComputed(array $commandArgs, string|null $expectedOrderBy): void
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
