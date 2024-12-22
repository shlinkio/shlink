<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Importer;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Shlinkio\Shlink\Core\RedirectRule\Entity\RedirectCondition;
use Shlinkio\Shlink\Core\RedirectRule\Entity\ShortUrlRedirectRule;
use Shlinkio\Shlink\Core\RedirectRule\ShortUrlRedirectRuleServiceInterface;
use Shlinkio\Shlink\Core\ShortUrl\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Visit\Entity\Visit;
use Shlinkio\Shlink\Importer\Model\ImportedShlinkRedirectRule;
use Shlinkio\Shlink\Importer\Model\ImportedShlinkVisit;

use function Shlinkio\Shlink\Core\ArrayUtils\map;
use function Shlinkio\Shlink\Core\normalizeDate;
use function sprintf;

final readonly class ShortUrlImporting
{
    private function __construct(private ShortUrl $shortUrl, private bool $isNew)
    {
    }

    public static function fromExistingShortUrl(ShortUrl $shortUrl): self
    {
        return new self($shortUrl, isNew: false);
    }

    public static function fromNewShortUrl(ShortUrl $shortUrl): self
    {
        return new self($shortUrl, isNew: true);
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
            if ($mostRecentImportedDate?->greaterThanOrEquals(normalizeDate($importedVisit->date))) {
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

    /**
     * @param ImportedShlinkRedirectRule[] $rules
     */
    public function importRedirectRules(
        array $rules,
        EntityManagerInterface $em,
        ShortUrlRedirectRuleServiceInterface $redirectRuleService,
    ): void {
        $shortUrl = $this->resolveShortUrl($em);
        $redirectRules = map(
            $rules,
            function (ImportedShlinkRedirectRule $rule, int|string|float $index) use ($shortUrl): ShortUrlRedirectRule {
                $conditions = new ArrayCollection();
                foreach ($rule->conditions as $cond) {
                    $redirectCondition = RedirectCondition::fromImport($cond);
                    if ($redirectCondition !== null) {
                        $conditions->add($redirectCondition);
                    }
                }

                return new ShortUrlRedirectRule(
                    shortUrl: $shortUrl,
                    priority: ((int) $index) + 1,
                    longUrl:$rule->longUrl,
                    conditions: $conditions,
                );
            },
        );

        $redirectRuleService->saveRulesForShortUrl($shortUrl, $redirectRules);
    }

    private function resolveShortUrl(EntityManagerInterface $em): ShortUrl
    {
        // If wrapped ShortUrl has no ID, avoid trying to query the EM, as it would fail in Postgres.
        // See https://github.com/shlinkio/shlink/issues/1947
        $id = $this->shortUrl->getId();
        if (!$id) {
            return $this->shortUrl;
        }

        // Instead of directly accessing wrapped ShortUrl entity, try to get it from the EM.
        // With this, we will get the same entity from memory if it is known by the EM, but if it was cleared, the EM
        // will fetch it again from the database, preventing errors at runtime.
        // However, if the EM was not flushed yet, the entity will not be found by ID, but it is known by the EM.
        // In that case, we fall back to wrapped ShortUrl entity directly.
        return $em->find(ShortUrl::class, $id) ?? $this->shortUrl;
    }
}
