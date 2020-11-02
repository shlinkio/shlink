<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\Importer;

use Cake\Chronos\Chronos;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\Core\Domain\Resolver\SimpleDomainResolver;
use Shlinkio\Shlink\Core\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Importer\ImportedLinksProcessor;
use Shlinkio\Shlink\Core\Repository\ShortUrlRepositoryInterface;
use Shlinkio\Shlink\Core\Service\ShortUrl\ShortCodeHelperInterface;
use Shlinkio\Shlink\Core\Util\DoctrineBatchHelperInterface;
use Shlinkio\Shlink\Importer\Model\ImportedShlinkUrl;
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

        $this->shortCodeHelper = $this->prophesize(ShortCodeHelperInterface::class);
        $batchHelper = $this->prophesize(DoctrineBatchHelperInterface::class);
        $batchHelper->wrapIterable(Argument::cetera())->willReturnArgument(0);

        $this->processor = new ImportedLinksProcessor(
            $this->em->reveal(),
            new SimpleDomainResolver(),
            $this->shortCodeHelper->reveal(),
            $batchHelper->reveal(),
        );

        $this->io = $this->prophesize(StyleInterface::class);
    }

    /** @test */
    public function newUrlsWithNoErrorsAreAllPersisted(): void
    {
        $urls = [
            new ImportedShlinkUrl('', 'foo', [], Chronos::now(), null, 'foo'),
            new ImportedShlinkUrl('', 'bar', [], Chronos::now(), null, 'bar'),
            new ImportedShlinkUrl('', 'baz', [], Chronos::now(), null, 'baz'),
        ];
        $expectedCalls = count($urls);

        $importedUrlExists = $this->repo->importedUrlExists(Argument::cetera())->willReturn(false);
        $ensureUniqueness = $this->shortCodeHelper->ensureShortCodeUniqueness(Argument::cetera())->willReturn(true);
        $persist = $this->em->persist(Argument::type(ShortUrl::class));

        $this->processor->process($this->io->reveal(), $urls, ['import_short_codes' => true]);

        $importedUrlExists->shouldHaveBeenCalledTimes($expectedCalls);
        $ensureUniqueness->shouldHaveBeenCalledTimes($expectedCalls);
        $persist->shouldHaveBeenCalledTimes($expectedCalls);
        $this->io->text(Argument::type('string'))->shouldHaveBeenCalledTimes($expectedCalls);
    }

    /** @test */
    public function alreadyImportedUrlsAreSkipped(): void
    {
        $urls = [
            new ImportedShlinkUrl('', 'foo', [], Chronos::now(), null, 'foo'),
            new ImportedShlinkUrl('', 'bar', [], Chronos::now(), null, 'bar'),
            new ImportedShlinkUrl('', 'baz', [], Chronos::now(), null, 'baz'),
            new ImportedShlinkUrl('', 'baz2', [], Chronos::now(), null, 'baz2'),
            new ImportedShlinkUrl('', 'baz3', [], Chronos::now(), null, 'baz3'),
        ];
        $contains = fn (string $needle) => fn (string $text) => str_contains($text, $needle);

        $importedUrlExists = $this->repo->importedUrlExists(Argument::cetera())->will(function (array $args): bool {
            /** @var ImportedShlinkUrl $url */
            [$url] = $args;

            return contains(['foo', 'baz2', 'baz3'], $url->longUrl());
        });
        $ensureUniqueness = $this->shortCodeHelper->ensureShortCodeUniqueness(Argument::cetera())->willReturn(true);
        $persist = $this->em->persist(Argument::type(ShortUrl::class));

        $this->processor->process($this->io->reveal(), $urls, ['import_short_codes' => true]);

        $importedUrlExists->shouldHaveBeenCalledTimes(count($urls));
        $ensureUniqueness->shouldHaveBeenCalledTimes(2);
        $persist->shouldHaveBeenCalledTimes(2);
        $this->io->text(Argument::that($contains('Skipped')))->shouldHaveBeenCalledTimes(3);
        $this->io->text(Argument::that($contains('Imported')))->shouldHaveBeenCalledTimes(2);
    }

    /** @test */
    public function nonUniqueShortCodesAreAskedToUser(): void
    {
        $urls = [
            new ImportedShlinkUrl('', 'foo', [], Chronos::now(), null, 'foo'),
            new ImportedShlinkUrl('', 'bar', [], Chronos::now(), null, 'bar'),
            new ImportedShlinkUrl('', 'baz', [], Chronos::now(), null, 'baz'),
            new ImportedShlinkUrl('', 'baz2', [], Chronos::now(), null, 'baz2'),
            new ImportedShlinkUrl('', 'baz3', [], Chronos::now(), null, 'baz3'),
        ];
        $contains = fn (string $needle) => fn (string $text) => str_contains($text, $needle);

        $importedUrlExists = $this->repo->importedUrlExists(Argument::cetera())->willReturn(false);
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

        $this->processor->process($this->io->reveal(), $urls, ['import_short_codes' => true]);

        $importedUrlExists->shouldHaveBeenCalledTimes(count($urls));
        $failingEnsureUniqueness->shouldHaveBeenCalledTimes(5);
        $successEnsureUniqueness->shouldHaveBeenCalledTimes(2);
        $choice->shouldHaveBeenCalledTimes(5);
        $persist->shouldHaveBeenCalledTimes(2);
        $this->io->text(Argument::that($contains('Skipped')))->shouldHaveBeenCalledTimes(3);
        $this->io->text(Argument::that($contains('Imported')))->shouldHaveBeenCalledTimes(2);
    }
}
