<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Importer;

use Cake\Chronos\Chronos;
use Doctrine\ORM\EntityManagerInterface;
use Shlinkio\Shlink\Core\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Entity\Visit;
use Shlinkio\Shlink\Importer\Model\ImportedShlinkVisit;

use function sprintf;

final class ShortUrlImporting
{
    private ShortUrl $shortUrl;
    private bool $isNew;

    private function __construct(ShortUrl $shortUrl, bool $isNew)
    {
        $this->shortUrl = $shortUrl;
        $this->isNew = $isNew;
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
     * @param iterable|ImportedShlinkVisit[] $visits
     */
    public function importVisits(iterable $visits, EntityManagerInterface $em): string
    {
        $mostRecentImportedDate = $this->shortUrl->mostRecentImportedVisitDate();

        $importedVisits = 0;
        foreach ($visits as $importedVisit) {
            // Skip visits which are older than the most recent already imported visit's date
            if (
                $mostRecentImportedDate !== null
                && $mostRecentImportedDate->gte(Chronos::instance($importedVisit->date()))
            ) {
                continue;
            }

            $em->persist(Visit::fromImport($this->shortUrl, $importedVisit));
            $importedVisits++;
        }

        if ($importedVisits === 0) {
            return $this->isNew ? '<info>Imported</info>' : '<comment>Skipped</comment>';
        }

        return $this->isNew
            ? sprintf('<info>Imported</info> with <info>%s</info> visits', $importedVisits)
            : sprintf('<comment>Skipped</comment>. Imported <info>%s</info> visits', $importedVisits);
    }
}
