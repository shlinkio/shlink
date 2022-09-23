<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Importer;

use Doctrine\ORM\EntityManagerInterface;
use Shlinkio\Shlink\Core\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Exception\NonUniqueSlugException;
use Shlinkio\Shlink\Core\ShortUrl\Repository\ShortUrlRepositoryInterface;
use Shlinkio\Shlink\Core\ShortUrl\Resolver\ShortUrlRelationResolverInterface;
use Shlinkio\Shlink\Core\ShortUrl\ShortCodeUniquenessHelperInterface;
use Shlinkio\Shlink\Core\Util\DoctrineBatchHelperInterface;
use Shlinkio\Shlink\Importer\ImportedLinksProcessorInterface;
use Shlinkio\Shlink\Importer\Model\ImportedShlinkUrl;
use Shlinkio\Shlink\Importer\Params\ImportParams;
use Shlinkio\Shlink\Importer\Sources\ImportSource;
use Symfony\Component\Console\Style\OutputStyle;
use Symfony\Component\Console\Style\StyleInterface;
use Throwable;

use function sprintf;

class ImportedLinksProcessor implements ImportedLinksProcessorInterface
{
    private ShortUrlRepositoryInterface $shortUrlRepo;

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly ShortUrlRelationResolverInterface $relationResolver,
        private readonly ShortCodeUniquenessHelperInterface $shortCodeHelper,
        private readonly DoctrineBatchHelperInterface $batchHelper,
    ) {
        $this->shortUrlRepo = $this->em->getRepository(ShortUrl::class);
    }

    /**
     * @param iterable<ImportedShlinkUrl> $shlinkUrls
     */
    public function process(StyleInterface $io, iterable $shlinkUrls, ImportParams $params): void
    {
        $importShortCodes = $params->importShortCodes;
        $source = $params->source;
        $iterable = $this->batchHelper->wrapIterable($shlinkUrls, $source === ImportSource::SHLINK ? 10 : 100);

        /** @var ImportedShlinkUrl $importedUrl */
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

            $resultMessage = $shortUrlImporting->importVisits($importedUrl->visits, $this->em);
            $io->text(sprintf('%s: %s', $longUrl, $resultMessage));
        }
    }

    private function resolveShortUrl(
        ImportedShlinkUrl $importedUrl,
        bool $importShortCodes,
        callable $skipOnShortCodeConflict,
    ): ShortUrlImporting {
        $alreadyImportedShortUrl = $this->shortUrlRepo->findOneByImportedUrl($importedUrl);
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
}
