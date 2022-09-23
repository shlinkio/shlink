<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\Importer;

use Cake\Chronos\Chronos;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use RuntimeException;
use Shlinkio\Shlink\Core\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Entity\Visit;
use Shlinkio\Shlink\Core\Importer\ImportedLinksProcessor;
use Shlinkio\Shlink\Core\Service\ShortUrl\ShortCodeUniquenessHelperInterface;
use Shlinkio\Shlink\Core\ShortUrl\Repository\ShortUrlRepositoryInterface;
use Shlinkio\Shlink\Core\ShortUrl\Resolver\SimpleShortUrlRelationResolver;
use Shlinkio\Shlink\Core\Util\DoctrineBatchHelperInterface;
use Shlinkio\Shlink\Importer\Model\ImportedShlinkUrl;
use Shlinkio\Shlink\Importer\Model\ImportedShlinkVisit;
use Shlinkio\Shlink\Importer\Params\ImportParams;
use Shlinkio\Shlink\Importer\Sources\ImportSource;
use Symfony\Component\Console\Style\StyleInterface;

use function count;
use function Functional\contains;
use function Functional\some;
use function str_contains;

class ImportedLinksProcessorTest extends TestCase
{
    use ProphecyTrait;

    private ImportedLinksProcessor $processor;
    private ObjectProphecy $em;
    private ObjectProphecy $shortCodeHelper;
    private ObjectProphecy $repo;
    private ObjectProphecy $io;

    protected function setUp(): void
    {
        $this->em = $this->prophesize(EntityManagerInterface::class);
        $this->repo = $this->prophesize(ShortUrlRepositoryInterface::class);
        $this->em->getRepository(ShortUrl::class)->willReturn($this->repo->reveal());

        $this->shortCodeHelper = $this->prophesize(ShortCodeUniquenessHelperInterface::class);
        $batchHelper = $this->prophesize(DoctrineBatchHelperInterface::class);
        $batchHelper->wrapIterable(Argument::cetera())->willReturnArgument(0);

        $this->processor = new ImportedLinksProcessor(
            $this->em->reveal(),
            new SimpleShortUrlRelationResolver(),
            $this->shortCodeHelper->reveal(),
            $batchHelper->reveal(),
        );

        $this->io = $this->prophesize(StyleInterface::class);
    }

    /** @test */
    public function newUrlsWithNoErrorsAreAllPersisted(): void
    {
        $urls = [
            new ImportedShlinkUrl(ImportSource::BITLY, 'foo', [], Chronos::now(), null, 'foo', null),
            new ImportedShlinkUrl(ImportSource::BITLY, 'bar', [], Chronos::now(), null, 'bar', 'foo'),
            new ImportedShlinkUrl(ImportSource::BITLY, 'baz', [], Chronos::now(), null, 'baz', null),
        ];
        $expectedCalls = count($urls);

        $importedUrlExists = $this->repo->findOneByImportedUrl(Argument::cetera())->willReturn(null);
        $ensureUniqueness = $this->shortCodeHelper->ensureShortCodeUniqueness(Argument::cetera())->willReturn(true);
        $persist = $this->em->persist(Argument::type(ShortUrl::class));

        $this->processor->process($this->io->reveal(), $urls, $this->buildParams());

        $importedUrlExists->shouldHaveBeenCalledTimes($expectedCalls);
        $ensureUniqueness->shouldHaveBeenCalledTimes($expectedCalls);
        $persist->shouldHaveBeenCalledTimes($expectedCalls);
        $this->io->text(Argument::type('string'))->shouldHaveBeenCalledTimes($expectedCalls);
    }

    /** @test */
    public function newUrlsWithErrorsAreSkipped(): void
    {
        $urls = [
            new ImportedShlinkUrl(ImportSource::BITLY, 'foo', [], Chronos::now(), null, 'foo', null),
            new ImportedShlinkUrl(ImportSource::BITLY, 'bar', [], Chronos::now(), null, 'bar', 'foo'),
            new ImportedShlinkUrl(ImportSource::BITLY, 'baz', [], Chronos::now(), null, 'baz', null),
        ];

        $importedUrlExists = $this->repo->findOneByImportedUrl(Argument::cetera())->willReturn(null);
        $ensureUniqueness = $this->shortCodeHelper->ensureShortCodeUniqueness(Argument::cetera())->willReturn(true);
        $persist = $this->em->persist(Argument::type(ShortUrl::class))->will(function (array $args): void {
            /** @var ShortUrl $shortUrl */
            [$shortUrl] = $args;

            if ($shortUrl->getShortCode() === 'baz') {
                throw new RuntimeException('Whatever error');
            }
        });

        $this->processor->process($this->io->reveal(), $urls, $this->buildParams());

        $importedUrlExists->shouldHaveBeenCalledTimes(3);
        $ensureUniqueness->shouldHaveBeenCalledTimes(3);
        $persist->shouldHaveBeenCalledTimes(3);
        $this->io->text(Argument::containingString('<info>Imported</info>'))->shouldHaveBeenCalledTimes(2);
        $this->io->text(
            Argument::containingString('<comment>Skipped</comment>. Reason: Whatever error'),
        )->shouldHaveBeenCalledOnce();
    }

    /** @test */
    public function alreadyImportedUrlsAreSkipped(): void
    {
        $urls = [
            new ImportedShlinkUrl(ImportSource::BITLY, 'foo', [], Chronos::now(), null, 'foo', null),
            new ImportedShlinkUrl(ImportSource::BITLY, 'bar', [], Chronos::now(), null, 'bar', null),
            new ImportedShlinkUrl(ImportSource::BITLY, 'baz', [], Chronos::now(), null, 'baz', null),
            new ImportedShlinkUrl(ImportSource::BITLY, 'baz2', [], Chronos::now(), null, 'baz2', null),
            new ImportedShlinkUrl(ImportSource::BITLY, 'baz3', [], Chronos::now(), null, 'baz3', null),
        ];

        $importedUrlExists = $this->repo->findOneByImportedUrl(Argument::cetera())->will(
            function (array $args): ?ShortUrl {
                /** @var ImportedShlinkUrl $url */
                [$url] = $args;

                return contains(['foo', 'baz2', 'baz3'], $url->longUrl) ? ShortUrl::fromImport($url, true) : null;
            },
        );
        $ensureUniqueness = $this->shortCodeHelper->ensureShortCodeUniqueness(Argument::cetera())->willReturn(true);
        $persist = $this->em->persist(Argument::type(ShortUrl::class));

        $this->processor->process($this->io->reveal(), $urls, $this->buildParams());

        $importedUrlExists->shouldHaveBeenCalledTimes(count($urls));
        $ensureUniqueness->shouldHaveBeenCalledTimes(2);
        $persist->shouldHaveBeenCalledTimes(2);
        $this->io->text(Argument::containingString('Skipped'))->shouldHaveBeenCalledTimes(3);
        $this->io->text(Argument::containingString('Imported'))->shouldHaveBeenCalledTimes(2);
    }

    /** @test */
    public function nonUniqueShortCodesAreAskedToUser(): void
    {
        $urls = [
            new ImportedShlinkUrl(ImportSource::BITLY, 'foo', [], Chronos::now(), null, 'foo', null),
            new ImportedShlinkUrl(ImportSource::BITLY, 'bar', [], Chronos::now(), null, 'bar', null),
            new ImportedShlinkUrl(ImportSource::BITLY, 'baz', [], Chronos::now(), null, 'baz', 'foo'),
            new ImportedShlinkUrl(ImportSource::BITLY, 'baz2', [], Chronos::now(), null, 'baz2', null),
            new ImportedShlinkUrl(ImportSource::BITLY, 'baz3', [], Chronos::now(), null, 'baz3', 'bar'),
        ];

        $importedUrlExists = $this->repo->findOneByImportedUrl(Argument::cetera())->willReturn(null);
        $failingEnsureUniqueness = $this->shortCodeHelper->ensureShortCodeUniqueness(
            Argument::any(),
            true,
        )->willReturn(false);
        $successEnsureUniqueness = $this->shortCodeHelper->ensureShortCodeUniqueness(
            Argument::any(),
            false,
        )->willReturn(true);
        $choice = $this->io->choice(Argument::cetera())->will(function (array $args) {
            /** @var ImportedShlinkUrl $url */
            [$question] = $args;

            return some(['foo', 'baz2', 'baz3'], fn (string $item) => str_contains($question, $item)) ? 'Skip' : '';
        });
        $persist = $this->em->persist(Argument::type(ShortUrl::class));

        $this->processor->process($this->io->reveal(), $urls, $this->buildParams());

        $importedUrlExists->shouldHaveBeenCalledTimes(count($urls));
        $failingEnsureUniqueness->shouldHaveBeenCalledTimes(5);
        $successEnsureUniqueness->shouldHaveBeenCalledTimes(2);
        $choice->shouldHaveBeenCalledTimes(5);
        $persist->shouldHaveBeenCalledTimes(2);
        $this->io->text(Argument::containingString('Error'))->shouldHaveBeenCalledTimes(3);
        $this->io->text(Argument::containingString('Imported'))->shouldHaveBeenCalledTimes(2);
    }

    /**
     * @test
     * @dataProvider provideUrlsWithVisits
     */
    public function properAmountOfVisitsIsImported(
        ImportedShlinkUrl $importedUrl,
        string $expectedOutput,
        int $amountOfPersistedVisits,
        ?ShortUrl $foundShortUrl,
    ): void {
        $findExisting = $this->repo->findOneByImportedUrl(Argument::cetera())->willReturn($foundShortUrl);
        $ensureUniqueness = $this->shortCodeHelper->ensureShortCodeUniqueness(Argument::cetera())->willReturn(true);
        $persistUrl = $this->em->persist(Argument::type(ShortUrl::class));
        $persistVisits = $this->em->persist(Argument::type(Visit::class));

        $this->processor->process($this->io->reveal(), [$importedUrl], $this->buildParams());

        $findExisting->shouldHaveBeenCalledOnce();
        $ensureUniqueness->shouldHaveBeenCalledTimes($foundShortUrl === null ? 1 : 0);
        $persistUrl->shouldHaveBeenCalledTimes($foundShortUrl === null ? 1 : 0);
        $persistVisits->shouldHaveBeenCalledTimes($amountOfPersistedVisits);
        $this->io->text(Argument::containingString($expectedOutput))->shouldHaveBeenCalledOnce();
    }

    public function provideUrlsWithVisits(): iterable
    {
        $now = Chronos::now();
        $createImportedUrl = static fn (array $visits) =>
            new ImportedShlinkUrl(ImportSource::BITLY, 's', [], $now, null, 's', null, $visits);

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
            ShortUrl::createEmpty(),
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
            ShortUrl::createEmpty()->setVisits(new ArrayCollection([
                Visit::fromImport(ShortUrl::createEmpty(), new ImportedShlinkVisit('', '', $now, null)),
            ])),
        ];
    }

    private function buildParams(): ImportParams
    {
        return ImportSource::BITLY->toParamsWithCallableMap(['import_short_codes' => static fn () => true]);
    }
}
