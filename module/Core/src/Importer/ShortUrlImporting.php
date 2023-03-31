<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Importer;

use Doctrine\ORM\EntityManagerInterface;
use Shlinkio\Shlink\Core\ShortUrl\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Visit\Entity\Visit;
use Shlinkio\Shlink\Importer\Model\ImportedShlinkVisit;

use function Shlinkio\Shlink\Core\normalizeDate;
use function sprintf;

final class ShortUrlImporting
{
    private function __construct(private readonly ShortUrl $shortUrl, private readonly bool $isNew)
    {
    }

    public static function fromExistingShortUrl(ShortUrl $shortUrl): self
    {
        return new self($shortUrl, false);
    }

    public static function fromNewShortUrl(ShortUrl $shortUrl): self
    {
        return new self($shortUrl, true);
    }

    /**
     * @param iterable<ImportedShlinkVisit> $visits
     */
    public function importVisits(iterable $visits, EntityManagerInterface $em): string
    {
        $mostRecentImportedDate = $this->resolveShortUrl($em)->mostRecentImportedVisitDate();

        $importedVisits = 0;
        foreach ($visits as $importedVisit) {
            // Skip visits which are older than the most recent already imported visit's date
            if ($mostRecentImportedDate?->gte(normalizeDate($importedVisit->date))) {
                continue;
            }

            $em->persist(Visit::fromImport($this->resolveShortUrl($em), $importedVisit));
            $importedVisits++;
        }

        if ($importedVisits === 0) {
            return $this->isNew ? '<info>Imported</info>' : '<comment>Skipped</comment>';
        }

        return $this->isNew
            ? sprintf('<info>Imported</info> with <info>%s</info> visits', $importedVisits)
            : sprintf('<comment>Skipped</comment>. Imported <info>%s</info> visits', $importedVisits);
    }

    private function resolveShortUrl(EntityManagerInterface $em): ShortUrl
    {
        // Instead of directly accessing wrapped ShortUrl entity, try to get it from the EM.
        // With this, we will get the same entity from memory if it is known by the EM, but if it was cleared, the EM
        // will fetch it again from the database, preventing errors at runtime.
        // However, if the EM was not flushed yet, the entity will not be found by ID, but it is known by the EM.
        // In that case, we fall back to wrapped ShortUrl entity directly.
        return $em->find(ShortUrl::class, $this->shortUrl->getId()) ?? $this->shortUrl;
    }
}
