<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Importer;

use Cake\Chronos\Chronos;
use Doctrine\ORM\EntityManagerInterface;
use Shlinkio\Shlink\Core\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Entity\Visit;
use Shlinkio\Shlink\Core\Repository\ShortUrlRepositoryInterface;
use Shlinkio\Shlink\Core\Service\ShortUrl\ShortCodeHelperInterface;
use Shlinkio\Shlink\Core\ShortUrl\Resolver\ShortUrlRelationResolverInterface;
use Shlinkio\Shlink\Core\Util\DoctrineBatchHelperInterface;
use Shlinkio\Shlink\Importer\ImportedLinksProcessorInterface;
use Shlinkio\Shlink\Importer\Model\ImportedShlinkUrl;
use Symfony\Component\Console\Style\StyleInterface;

use function sprintf;

class ImportedLinksProcessor implements ImportedLinksProcessorInterface
{
    private EntityManagerInterface $em;
    private ShortUrlRelationResolverInterface $relationResolver;
    private ShortCodeHelperInterface $shortCodeHelper;
    private DoctrineBatchHelperInterface $batchHelper;
    private ShortUrlRepositoryInterface $shortUrlRepo;

    public function __construct(
        EntityManagerInterface $em,
        ShortUrlRelationResolverInterface $relationResolver,
        ShortCodeHelperInterface $shortCodeHelper,
        DoctrineBatchHelperInterface $batchHelper
    ) {
        $this->em = $em;
        $this->relationResolver = $relationResolver;
        $this->shortCodeHelper = $shortCodeHelper;
        $this->batchHelper = $batchHelper;
        $this->shortUrlRepo = $this->em->getRepository(ShortUrl::class); // @phpstan-ignore-line
    }

    /**
     * @param iterable|ImportedShlinkUrl[] $shlinkUrls
     */
    public function process(StyleInterface $io, iterable $shlinkUrls, array $params): void
    {
        $importShortCodes = $params['import_short_codes'];
        $iterable = $this->batchHelper->wrapIterable($shlinkUrls, 100);

        /** @var ImportedShlinkUrl $importedUrl */
        foreach ($iterable as $importedUrl) {
            $skipOnShortCodeConflict = static function () use ($io, $importedUrl): bool {
                $action = $io->choice(sprintf(
                    'Failed to import URL "%s" because its short-code "%s" is already in use. Do you want to generate '
                    . 'a new one or skip it?',
                    $importedUrl->longUrl(),
                    $importedUrl->shortCode(),
                ), ['Generate new short-code', 'Skip'], 1);

                return $action === 'Skip';
            };
            [$shortUrl, $isNew] = $this->getOrCreateShortUrl($importedUrl, $importShortCodes, $skipOnShortCodeConflict);

            $longUrl = $importedUrl->longUrl();
            if ($shortUrl === null) {
                $io->text(sprintf('%s: <fg=red>Error</>', $longUrl));
                continue;
            }

            $importedVisits = $this->importVisits($importedUrl, $shortUrl);

            if ($importedVisits === 0) {
                $io->text(
                    $isNew
                        ? sprintf('%s: <info>Imported</info>', $longUrl)
                        : sprintf('%s: <comment>Skipped</comment>', $longUrl),
                );
            } else {
                $io->text(
                    $isNew
                        ? sprintf('%s: <info>Imported</info> with <info>%s</info> visits', $longUrl, $importedVisits)
                        : sprintf(
                            '%s: <comment>Skipped</comment>. Imported <info>%s</info> visits',
                            $longUrl,
                            $importedVisits,
                        ),
                );
            }
        }
    }

    private function getOrCreateShortUrl(
        ImportedShlinkUrl $importedUrl,
        bool $importShortCodes,
        callable $skipOnShortCodeConflict
    ): array {
        $alreadyImportedShortUrl = $this->shortUrlRepo->findOneByImportedUrl($importedUrl);
        if ($alreadyImportedShortUrl !== null) {
            return [$alreadyImportedShortUrl, false];
        }

        $shortUrl = ShortUrl::fromImport($importedUrl, $importShortCodes, $this->relationResolver);
        if (! $this->handleShortCodeUniqueness($shortUrl, $importShortCodes, $skipOnShortCodeConflict)) {
            return [null, false];
        }

        $this->em->persist($shortUrl);
        return [$shortUrl, true];
    }

    private function handleShortCodeUniqueness(
        ShortUrl $shortUrl,
        bool $importShortCodes,
        callable $skipOnShortCodeConflict
    ): bool {
        if ($this->shortCodeHelper->ensureShortCodeUniqueness($shortUrl, $importShortCodes)) {
            return true;
        }

        if ($skipOnShortCodeConflict()) {
            return false;
        }

        return $this->shortCodeHelper->ensureShortCodeUniqueness($shortUrl, false);
    }

    private function importVisits(ImportedShlinkUrl $importedUrl, ShortUrl $shortUrl): int
    {
        $mostRecentImportedDate = $shortUrl->mostRecentImportedVisitDate();

        $importedVisits = 0;
        foreach ($importedUrl->visits() as $importedVisit) {
            // Skip visits which are older than the most recent already imported visit's date
            if (
                $mostRecentImportedDate !== null
                && $mostRecentImportedDate->gte(Chronos::instance($importedVisit->date()))
            ) {
                continue;
            }

            $this->em->persist(Visit::fromImport($shortUrl, $importedVisit));
            $importedVisits++;
        }

        return $importedVisits;
    }
}
