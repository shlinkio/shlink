<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Importer;

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
    }

    /**
     * @param iterable|ImportedShlinkUrl[] $shlinkUrls
     */
    public function process(StyleInterface $io, iterable $shlinkUrls, array $params): void
    {
        /** @var ShortUrlRepositoryInterface $shortUrlRepo */
        $shortUrlRepo = $this->em->getRepository(ShortUrl::class);
        $importShortCodes = $params['import_short_codes'];
        $iterable = $this->batchHelper->wrapIterable($shlinkUrls, 100);

        /** @var ImportedShlinkUrl $importedUrl */
        foreach ($iterable as $importedUrl) {
            $longUrl = $importedUrl->longUrl();

            // Skip already imported URLs
            if ($shortUrlRepo->importedUrlExists($importedUrl)) {
                // TODO If the URL exists, allow to merge visits instead of just skipping completely
                $io->text(sprintf('%s: <comment>Skipped</comment>', $longUrl));
                continue;
            }

            $shortUrl = ShortUrl::fromImport($importedUrl, $importShortCodes, $this->relationResolver);
            if (! $this->handleShortCodeUniqueness($importedUrl, $shortUrl, $io, $importShortCodes)) {
                $io->text(sprintf('%s: <comment>Skipped</comment>', $longUrl));
                continue;
            }

            $this->em->persist($shortUrl);

            // TODO Process only missing visits when possible: $importedUrl->visitsCount();
            // TODO Make importing visits optional based on params
            $importedVisits = 0;
            foreach ($importedUrl->visits() as $importedVisit) {
                $this->em->persist(Visit::fromImport($importedVisit, $shortUrl));
                $importedVisits++;
            }

            $io->text(
                $importedVisits === 0
                    ? sprintf('%s: <info>Imported</info>', $longUrl)
                    : sprintf('%s: <info>Imported</info> with <info>%s</info> visits', $longUrl, $importedVisits),
            );
        }
    }

    private function handleShortCodeUniqueness(
        ImportedShlinkUrl $url,
        ShortUrl $shortUrl,
        StyleInterface $io,
        bool $importShortCodes
    ): bool {
        if ($this->shortCodeHelper->ensureShortCodeUniqueness($shortUrl, $importShortCodes)) {
            return true;
        }

        $longUrl = $url->longUrl();
        $action = $io->choice(sprintf(
            'Failed to import URL "%s" because its short-code "%s" is already in use. Do you want to generate a new '
            . 'one or skip it?',
            $longUrl,
            $url->shortCode(),
        ), ['Generate new short-code', 'Skip'], 1);

        if ($action === 'Skip') {
            return false;
        }

        return $this->shortCodeHelper->ensureShortCodeUniqueness($shortUrl, false);
    }
}
