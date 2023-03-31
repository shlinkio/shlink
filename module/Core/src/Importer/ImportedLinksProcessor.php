<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Importer;

use Doctrine\ORM\EntityManagerInterface;
use Shlinkio\Shlink\Core\Exception\NonUniqueSlugException;
use Shlinkio\Shlink\Core\ShortUrl\Entity\ShortUrl;
use Shlinkio\Shlink\Core\ShortUrl\Helper\ShortCodeUniquenessHelperInterface;
use Shlinkio\Shlink\Core\ShortUrl\Repository\ShortUrlRepositoryInterface;
use Shlinkio\Shlink\Core\ShortUrl\Resolver\ShortUrlRelationResolverInterface;
use Shlinkio\Shlink\Core\Util\DoctrineBatchHelperInterface;
use Shlinkio\Shlink\Core\Visit\Entity\Visit;
use Shlinkio\Shlink\Core\Visit\Repository\VisitRepositoryInterface;
use Shlinkio\Shlink\Importer\ImportedLinksProcessorInterface;
use Shlinkio\Shlink\Importer\Model\ImportedShlinkOrphanVisit;
use Shlinkio\Shlink\Importer\Model\ImportedShlinkUrl;
use Shlinkio\Shlink\Importer\Model\ImportResult;
use Shlinkio\Shlink\Importer\Params\ImportParams;
use Symfony\Component\Console\Style\OutputStyle;
use Symfony\Component\Console\Style\StyleInterface;
use Throwable;

use function Shlinkio\Shlink\Core\normalizeDate;
use function sprintf;

class ImportedLinksProcessor implements ImportedLinksProcessorInterface
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly ShortUrlRelationResolverInterface $relationResolver,
        private readonly ShortCodeUniquenessHelperInterface $shortCodeHelper,
        private readonly DoctrineBatchHelperInterface $batchHelper,
    ) {
    }

    public function process(StyleInterface $io, ImportResult $result, ImportParams $params): void
    {
        $io->title('Importing short URLs');
        $this->importShortUrls($io, $result->shlinkUrls, $params);

        if ($params->importOrphanVisits) {
            $io->title('Importing orphan visits');
            $this->importOrphanVisits($io, $result->orphanVisits);
        }

        $io->success('Data properly imported!');
    }

    /**
     * @param iterable<ImportedShlinkUrl> $shlinkUrls
     */
    private function importShortUrls(StyleInterface $io, iterable $shlinkUrls, ImportParams $params): void
    {
        $importShortCodes = $params->importShortCodes;
        $iterable = $this->batchHelper->wrapIterable($shlinkUrls, $params->importVisits ? 10 : 100);

        foreach ($iterable as $importedUrl) {
            $skipOnShortCodeConflict = static fn (): bool => $io->choice(sprintf(
                'Failed to import URL "%s" because its short-code "%s" is already in use. Do you want to generate '
                . 'a new one or skip it?',
                $importedUrl->longUrl,
                $importedUrl->shortCode,
            ), ['Generate new short-code', 'Skip'], 1) === 'Skip';
            $longUrl = $importedUrl->longUrl;

            try {
                $shortUrlImporting = $this->resolveShortUrl($importedUrl, $importShortCodes, $skipOnShortCodeConflict);
            } catch (NonUniqueSlugException) {
                $io->text(sprintf('%s: <fg=red>Error</>', $longUrl));
                continue;
            } catch (Throwable $e) {
                $io->text(sprintf('%s: <comment>Skipped</comment>. Reason: %s.', $longUrl, $e->getMessage()));

                if ($io instanceof OutputStyle && $io->isVerbose()) {
                    $io->text($e->__toString());
                }

                continue;
            }

            $resultMessage = $shortUrlImporting->importVisits(
                $this->batchHelper->wrapIterable($importedUrl->visits, 100),
                $this->em,
            );
            $io->text(sprintf('%s: %s', $longUrl, $resultMessage));
        }
    }

    private function resolveShortUrl(
        ImportedShlinkUrl $importedUrl,
        bool $importShortCodes,
        callable $skipOnShortCodeConflict,
    ): ShortUrlImporting {
        /** @var ShortUrlRepositoryInterface $shortUrlRepo */
        $shortUrlRepo = $this->em->getRepository(ShortUrl::class);
        $alreadyImportedShortUrl = $shortUrlRepo->findOneByImportedUrl($importedUrl);
        if ($alreadyImportedShortUrl !== null) {
            return ShortUrlImporting::fromExistingShortUrl($alreadyImportedShortUrl);
        }

        $shortUrl = ShortUrl::fromImport($importedUrl, $importShortCodes, $this->relationResolver);
        if (! $this->handleShortCodeUniqueness($shortUrl, $importShortCodes, $skipOnShortCodeConflict)) {
            throw NonUniqueSlugException::fromImport($importedUrl);
        }

        $this->em->persist($shortUrl);
        return ShortUrlImporting::fromNewShortUrl($shortUrl);
    }

    private function handleShortCodeUniqueness(
        ShortUrl $shortUrl,
        bool $importShortCodes,
        callable $skipOnShortCodeConflict,
    ): bool {
        if ($this->shortCodeHelper->ensureShortCodeUniqueness($shortUrl, $importShortCodes)) {
            return true;
        }

        if ($skipOnShortCodeConflict()) {
            return false;
        }

        return $this->shortCodeHelper->ensureShortCodeUniqueness($shortUrl, false);
    }

    /**
     * @param iterable<ImportedShlinkOrphanVisit> $orphanVisits
     */
    private function importOrphanVisits(StyleInterface $io, iterable $orphanVisits): void
    {
        $iterable = $this->batchHelper->wrapIterable($orphanVisits, 100);

        /** @var VisitRepositoryInterface $visitRepo */
        $visitRepo = $this->em->getRepository(Visit::class);
        $mostRecentOrphanVisit = $visitRepo->findMostRecentOrphanVisit();

        $importedVisits = 0;
        foreach ($iterable as $importedOrphanVisit) {
            // Skip visits which are older than the most recent already imported visit's date
            if ($mostRecentOrphanVisit?->getDate()->gte(normalizeDate($importedOrphanVisit->date))) {
                continue;
            }

            $this->em->persist(Visit::fromOrphanImport($importedOrphanVisit));
            $importedVisits++;
        }

        $io->text(sprintf('<info>Imported %s</info> orphan visits.', $importedVisits));
    }
}
