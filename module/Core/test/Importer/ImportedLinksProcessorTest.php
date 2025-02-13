<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\Importer;

use Cake\Chronos\Chronos;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Shlinkio\Shlink\Core\Importer\ImportedLinksProcessor;
use Shlinkio\Shlink\Core\Model\DeviceType;
use Shlinkio\Shlink\Core\RedirectRule\Entity\ShortUrlRedirectRule;
use Shlinkio\Shlink\Core\RedirectRule\Model\RedirectConditionType;
use Shlinkio\Shlink\Core\RedirectRule\ShortUrlRedirectRuleServiceInterface;
use Shlinkio\Shlink\Core\ShortUrl\Entity\ShortUrl;
use Shlinkio\Shlink\Core\ShortUrl\Helper\ShortCodeUniquenessHelperInterface;
use Shlinkio\Shlink\Core\ShortUrl\Repository\ShortUrlRepository;
use Shlinkio\Shlink\Core\ShortUrl\Resolver\SimpleShortUrlRelationResolver;
use Shlinkio\Shlink\Core\Util\DoctrineBatchHelperInterface;
use Shlinkio\Shlink\Core\Visit\Entity\Visit;
use Shlinkio\Shlink\Core\Visit\Model\Visitor;
use Shlinkio\Shlink\Core\Visit\Repository\VisitRepository;
use Shlinkio\Shlink\Importer\Model\ImportedShlinkOrphanVisit;
use Shlinkio\Shlink\Importer\Model\ImportedShlinkRedirectCondition;
use Shlinkio\Shlink\Importer\Model\ImportedShlinkRedirectRule;
use Shlinkio\Shlink\Importer\Model\ImportedShlinkUrl;
use Shlinkio\Shlink\Importer\Model\ImportedShlinkVisit;
use Shlinkio\Shlink\Importer\Model\ImportResult;
use Shlinkio\Shlink\Importer\Params\ImportParams;
use Shlinkio\Shlink\Importer\Sources\ImportSource;
use stdClass;
use Symfony\Component\Console\Style\StyleInterface;

use function count;
use function Shlinkio\Shlink\Core\ArrayUtils\contains;
use function Shlinkio\Shlink\Core\ArrayUtils\some;
use function sprintf;
use function str_contains;

class ImportedLinksProcessorTest extends TestCase
{
    private ImportedLinksProcessor $processor;
    private MockObject & EntityManagerInterface $em;
    private MockObject & ShortCodeUniquenessHelperInterface $shortCodeHelper;
    private MockObject & ShortUrlRepository $repo;
    private MockObject & StyleInterface $io;
    private MockObject & ShortUrlRedirectRuleServiceInterface $redirectRuleService;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->repo = $this->createMock(ShortUrlRepository::class);
        $this->shortCodeHelper = $this->createMock(ShortCodeUniquenessHelperInterface::class);
        $this->redirectRuleService = $this->createMock(ShortUrlRedirectRuleServiceInterface::class);

        $batchHelper = $this->createMock(DoctrineBatchHelperInterface::class);
        $batchHelper->method('wrapIterable')->willReturnArgument(0);

        $this->processor = new ImportedLinksProcessor(
            $this->em,
            new SimpleShortUrlRelationResolver(),
            $this->shortCodeHelper,
            $batchHelper,
            $this->redirectRuleService,
        );

        $this->io = $this->createMock(StyleInterface::class);
    }

    #[Test]
    public function newUrlsWithNoErrorsAreAllPersisted(): void
    {
        $now = Chronos::now();
        $urls = [
            new ImportedShlinkUrl(ImportSource::BITLY, 'https://foo', [], $now, null, 'foo', null),
            new ImportedShlinkUrl(ImportSource::BITLY, 'https://bar', [], $now, null, 'bar', 'foo'),
            new ImportedShlinkUrl(ImportSource::BITLY, 'https://baz', [], $now, null, 'baz', null, redirectRules: [
                new ImportedShlinkRedirectRule(
                    longUrl: 'https://example.com/android',
                    conditions: [
                        new ImportedShlinkRedirectCondition(
                            RedirectConditionType::DEVICE->value,
                            DeviceType::ANDROID->value,
                        ),
                    ],
                ),
                new ImportedShlinkRedirectRule(
                    longUrl: 'https://example.com/spain',
                    conditions: [
                        new ImportedShlinkRedirectCondition(
                            RedirectConditionType::GEOLOCATION_COUNTRY_CODE->value,
                            'ES',
                        ),
                        new ImportedShlinkRedirectCondition(RedirectConditionType::LANGUAGE->value, 'es-ES'),
                    ],
                ),
            ]),
        ];
        $expectedCalls = count($urls);

        $this->em->method('getRepository')->willReturn($this->repo);
        $this->repo->expects($this->exactly($expectedCalls))->method('findOneByImportedUrl')->willReturn(null);
        $this->shortCodeHelper->expects($this->exactly($expectedCalls))
                              ->method('ensureShortCodeUniqueness')
                              ->willReturn(true);
        $this->em->expects($this->exactly($expectedCalls))->method('persist')->with(
            $this->isInstanceOf(ShortUrl::class),
        );
        $this->io->expects($this->exactly($expectedCalls))->method('text')->with($this->isString());
        $this->redirectRuleService->expects($this->once())->method('saveRulesForShortUrl')->with(
            $this->isInstanceOf(ShortUrl::class),
            $this->callback(function (array $rules): bool {
                Assert::assertCount(2, $rules);
                Assert::assertInstanceOf(ShortUrlRedirectRule::class, $rules[0]);
                Assert::assertInstanceOf(ShortUrlRedirectRule::class, $rules[1]);
                Assert::assertCount(1, $rules[0]->mapConditions(fn ($c) => $c));
                Assert::assertCount(2, $rules[1]->mapConditions(fn ($c) => $c));

                return true;
            }),
        );

        $this->processor->process($this->io, ImportResult::withShortUrls($urls), $this->buildParams());
    }

    #[Test]
    public function newUrlsWithErrorsAreSkipped(): void
    {
        $urls = [
            new ImportedShlinkUrl(ImportSource::BITLY, 'https://foo', [], Chronos::now(), null, 'foo', null),
            new ImportedShlinkUrl(ImportSource::BITLY, 'https://bar', [], Chronos::now(), null, 'bar', 'foo'),
            new ImportedShlinkUrl(ImportSource::BITLY, 'https://baz', [], Chronos::now(), null, 'baz', null),
        ];

        $this->em->method('getRepository')->willReturn($this->repo);
        $this->repo->expects($this->exactly(3))->method('findOneByImportedUrl')->willReturn(null);
        $this->shortCodeHelper->expects($this->exactly(3))->method('ensureShortCodeUniqueness')->willReturn(true);
        $this->em->expects($this->exactly(3))->method('persist')->with(
            $this->isInstanceOf(ShortUrl::class),
        )->willReturnCallback(function (ShortUrl $shortUrl): void {
            if ($shortUrl->getShortCode() === 'baz') {
                throw new RuntimeException('Whatever error');
            }
        });
        $textCalls = $this->setUpIoText('<comment>Skipped</comment>. Reason: Whatever error', '<info>Imported</info>');

        $this->processor->process($this->io, ImportResult::withShortUrls($urls), $this->buildParams());

        self::assertEquals(2, $textCalls->importedCount);
        self::assertEquals(1, $textCalls->skippedCount);
    }

    #[Test]
    public function alreadyImportedUrlsAreSkipped(): void
    {
        $urls = [
            new ImportedShlinkUrl(ImportSource::BITLY, 'https://foo', [], Chronos::now(), null, 'foo', null),
            new ImportedShlinkUrl(ImportSource::BITLY, 'https://bar', [], Chronos::now(), null, 'bar', null),
            new ImportedShlinkUrl(ImportSource::BITLY, 'https://baz', [], Chronos::now(), null, 'baz', null),
            new ImportedShlinkUrl(ImportSource::BITLY, 'https://baz2', [], Chronos::now(), null, 'baz2', null),
            new ImportedShlinkUrl(ImportSource::BITLY, 'https://baz3', [], Chronos::now(), null, 'baz3', null),
        ];

        $this->em->method('getRepository')->willReturn($this->repo);
        $this->repo->expects($this->exactly(count($urls)))->method('findOneByImportedUrl')->willReturnCallback(
            fn (ImportedShlinkUrl $url): ShortUrl|null => contains(
                $url->longUrl,
                ['https://foo', 'https://baz2', 'https://baz3'],
            ) ? ShortUrl::fromImport($url, true) : null,
        );
        $this->shortCodeHelper->expects($this->exactly(2))->method('ensureShortCodeUniqueness')->willReturn(true);
        $this->em->expects($this->exactly(2))->method('persist')->with($this->isInstanceOf(ShortUrl::class));
        $textCalls = $this->setUpIoText();

        $this->processor->process($this->io, ImportResult::withShortUrls($urls), $this->buildParams());

        self::assertEquals(2, $textCalls->importedCount);
        self::assertEquals(3, $textCalls->skippedCount);
    }

    #[Test]
    public function nonUniqueShortCodesAreAskedToUser(): void
    {
        $urls = [
            new ImportedShlinkUrl(ImportSource::BITLY, 'https://foo', [], Chronos::now(), null, 'foo', null),
            new ImportedShlinkUrl(ImportSource::BITLY, 'https://bar', [], Chronos::now(), null, 'bar', null),
            new ImportedShlinkUrl(ImportSource::BITLY, 'https://baz', [], Chronos::now(), null, 'baz', 'foo'),
            new ImportedShlinkUrl(ImportSource::BITLY, 'https://baz2', [], Chronos::now(), null, 'baz2', null),
            new ImportedShlinkUrl(ImportSource::BITLY, 'https://baz3', [], Chronos::now(), null, 'baz3', 'bar'),
        ];

        $this->em->method('getRepository')->willReturn($this->repo);
        $this->repo->expects($this->exactly(count($urls)))->method('findOneByImportedUrl')->willReturn(null);
        $this->shortCodeHelper->expects($this->exactly(7))->method('ensureShortCodeUniqueness')->willReturnCallback(
            fn ($_, bool $hasCustomSlug) => ! $hasCustomSlug,
        );
        $this->em->expects($this->exactly(2))->method('persist')->with($this->isInstanceOf(ShortUrl::class));
        $this->io->expects($this->exactly(5))->method('choice')->willReturnCallback(function (string $question) {
            return some(['foo', 'baz2', 'baz3'], fn (string $item) => str_contains($question, $item)) ? 'Skip' : '';
        });
        $textCalls = $this->setUpIoText('Error');

        $this->processor->process($this->io, ImportResult::withShortUrls($urls), $this->buildParams());

        self::assertEquals(2, $textCalls->importedCount);
        self::assertEquals(3, $textCalls->skippedCount);
    }

    #[Test, DataProvider('provideUrlsWithVisits')]
    public function properAmountOfVisitsIsImported(
        ImportedShlinkUrl $importedUrl,
        string $expectedOutput,
        int $amountOfPersistedVisits,
        ShortUrl|null $foundShortUrl,
    ): void {
        $this->em->method('getRepository')->willReturn($this->repo);
        $this->repo->expects($this->once())->method('findOneByImportedUrl')->willReturn($foundShortUrl);
        $this->shortCodeHelper->expects($this->exactly($foundShortUrl === null ? 1 : 0))
                              ->method('ensureShortCodeUniqueness')
                              ->willReturn(true);
        $this->em->expects($this->exactly($amountOfPersistedVisits + ($foundShortUrl === null ? 1 : 0)))->method(
            'persist',
        )->with($this->callback(fn (object $arg) => $arg instanceof ShortUrl || $arg instanceof Visit));
        $this->em->expects($this->any())->method('find')->willReturn(null);
        $this->io->expects($this->once())->method('text')->with($this->stringContains($expectedOutput));

        $this->processor->process($this->io, ImportResult::withShortUrls([$importedUrl]), $this->buildParams());
    }

    public static function provideUrlsWithVisits(): iterable
    {
        $now = Chronos::now();
        $createImportedUrl = static fn (array $visits) =>
            new ImportedShlinkUrl(ImportSource::BITLY, 'https://s', [], $now, null, 's', null, $visits);

        yield 'new short URL' => [$createImportedUrl([
            new ImportedShlinkVisit('', '', $now, null),
            new ImportedShlinkVisit('', '', $now, null),
            new ImportedShlinkVisit('', '', $now, null),
            new ImportedShlinkVisit('', '', $now, null),
            new ImportedShlinkVisit('', '', $now, null),
        ]), '<info>Imported</info> with <info>5</info> visits', 5, null];
        yield 'existing short URL without previous imported visits' => [
            $createImportedUrl([
                new ImportedShlinkVisit('', '', $now, null),
                new ImportedShlinkVisit('', '', $now, null),
                new ImportedShlinkVisit('', '', $now->addDays(3), null),
                new ImportedShlinkVisit('', '', $now->addDays(3), null),
            ]),
            '<comment>Skipped</comment>. Imported <info>4</info> visits',
            4,
            ShortUrl::createFake(),
        ];
        yield 'existing short URL with previous imported visits' => [
            $createImportedUrl([
                new ImportedShlinkVisit('', '', $now, null),
                new ImportedShlinkVisit('', '', $now, null),
                new ImportedShlinkVisit('', '', $now, null),
                new ImportedShlinkVisit('', '', $now->addDays(3), null),
                new ImportedShlinkVisit('', '', $now->addDays(3), null),
            ]),
            '<comment>Skipped</comment>. Imported <info>2</info> visits',
            2,
            ShortUrl::createFake()->setVisits(new ArrayCollection([
                Visit::fromImport(ShortUrl::createFake(), new ImportedShlinkVisit('', '', $now, null)),
            ])),
        ];
    }

    #[Test, DataProvider('provideFoundShortUrls')]
    public function visitsArePersistedWithProperShortUrl(ShortUrl $originalShortUrl, ShortUrl|null $foundShortUrl): void
    {
        $this->em->method('getRepository')->willReturn($this->repo);
        $this->repo->expects($this->once())->method('findOneByImportedUrl')->willReturn($originalShortUrl);
        if (!$originalShortUrl->getId()) {
            $this->em->expects($this->never())->method('find');
        } else {
            // 3 times: Initial short URL checking, before creating redirect rules, before creating visits
            $this->em->expects($this->exactly(3))->method('find')->willReturn($foundShortUrl);
        }
        $this->em->expects($this->once())->method('persist')->willReturnCallback(
            static fn (Visit $visit)  => Assert::assertSame(
                $foundShortUrl ?? $originalShortUrl,
                $visit->shortUrl,
            ),
        );

        $now = Chronos::now();
        $this->processor->process($this->io, ImportResult::withShortUrls([
            new ImportedShlinkUrl(ImportSource::SHLINK, 'https://s', [], $now, null, 's', null, [
                new ImportedShlinkVisit('', '', $now, null),
            ]),
        ]), $this->buildParams());
    }

    public static function provideFoundShortUrls(): iterable
    {
        yield 'not found new URL' => [ShortUrl::withLongUrl('https://foo')->setId('123'), null];
        yield 'found new URL' => [
            ShortUrl::withLongUrl('https://foo')->setId('123'),
            ShortUrl::withLongUrl('https://bar'),
        ];
        yield 'old URL without ID' => [$originalShortUrl = ShortUrl::withLongUrl('https://foo'), $originalShortUrl];
    }

    /**
     * @param iterable<ImportedShlinkOrphanVisit> $visits
     */
    #[Test, DataProvider('provideOrphanVisits')]
    public function properAmountOfOrphanVisitsIsImported(
        bool $importOrphanVisits,
        iterable $visits,
        Visit|null $lastOrphanVisit,
        int $expectedImportedVisits,
    ): void {
        $this->io->expects($this->exactly($importOrphanVisits ? 2 : 1))->method('title');
        $this->io->expects($importOrphanVisits ? $this->once() : $this->never())->method('text')->with(
            sprintf('<info>Imported %s</info> orphan visits.', $expectedImportedVisits),
        );

        $visitRepo = $this->createMock(VisitRepository::class);
        $visitRepo->expects($importOrphanVisits ? $this->once() : $this->never())->method(
            'findMostRecentOrphanVisit',
        )->willReturn($lastOrphanVisit);
        $this->em->expects($importOrphanVisits ? $this->once() : $this->never())->method('getRepository')->with(
            Visit::class,
        )->willReturn($visitRepo);
        $this->em->expects($importOrphanVisits ? $this->exactly($expectedImportedVisits) : $this->never())->method(
            'persist',
        )->with($this->isInstanceOf(Visit::class));

        $this->processor->process(
            $this->io,
            ImportResult::withShortUrlsAndOrphanVisits([], $visits),
            $this->buildParams($importOrphanVisits),
        );
    }

    public static function provideOrphanVisits(): iterable
    {
        yield 'import orphan disable without visits' => [false, [], null, 0];
        yield 'import orphan enabled without visits' => [true, [], null, 0];
        yield 'import orphan disabled with visits' => [false, [
            new ImportedShlinkOrphanVisit('', '', Chronos::now(), '', '', null),
        ], null, 0];
        yield 'import orphan enabled with visits' => [true, [
            new ImportedShlinkOrphanVisit('', '', Chronos::now(), '', '', null),
            new ImportedShlinkOrphanVisit('', '', Chronos::now(), '', '', null),
            new ImportedShlinkOrphanVisit('', '', Chronos::now(), '', '', null),
            new ImportedShlinkOrphanVisit('', '', Chronos::now(), '', '', null),
            new ImportedShlinkOrphanVisit('', '', Chronos::now(), '', '', null),
        ], null, 5];
        yield 'existing orphan visit' => [true, [
            new ImportedShlinkOrphanVisit('', '', Chronos::now()->subDays(3), '', '', null),
            new ImportedShlinkOrphanVisit('', '', Chronos::now()->subDays(2), '', '', null),
            new ImportedShlinkOrphanVisit('', '', Chronos::now()->addDays(1), '', '', null),
            new ImportedShlinkOrphanVisit('', '', Chronos::now()->addDays(1), '', '', null),
            new ImportedShlinkOrphanVisit('', '', Chronos::now()->addDays(1), '', '', null),
        ], Visit::forBasePath(Visitor::botInstance()), 3];
    }

    private function buildParams(bool $importOrphanVisits = false): ImportParams
    {
        return ImportSource::BITLY->toParamsWithCallableMap([
            ImportParams::IMPORT_SHORT_CODES_PARAM => static fn () => true,
            ImportParams::IMPORT_ORPHAN_VISITS_PARAM => static fn () => $importOrphanVisits,
        ]);
    }

    public function setUpIoText(string $skippedText = 'Skipped', string $importedText = 'Imported'): stdClass
    {
        $counts = new stdClass();
        $counts->importedCount = 0;
        $counts->skippedCount = 0;

        $this->io->method('text')->willReturnCallback(
            function (string $output) use ($counts, $skippedText, $importedText): void {
                if (str_contains($output, $skippedText)) {
                    $counts->skippedCount++;
                } elseif (str_contains($output, $importedText)) {
                    $counts->importedCount++;
                }
            },
        );

        return $counts;
    }
}
