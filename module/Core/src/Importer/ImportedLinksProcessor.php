<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Importer;

use Doctrine\ORM\EntityManagerInterface;
use Shlinkio\Shlink\Core\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Exception\NonUniqueSlugException;
use Shlinkio\Shlink\Core\Repository\ShortUrlRepositoryInterface;
use Shlinkio\Shlink\Core\Service\ShortUrl\ShortCodeHelperInterface;
use Shlinkio\Shlink\Core\ShortUrl\Resolver\ShortUrlRelationResolverInterface;
use Shlinkio\Shlink\Core\Util\DoctrineBatchHelperInterface;
use Shlinkio\Shlink\Importer\ImportedLinksProcessorInterface;
use Shlinkio\Shlink\Importer\Model\ImportedShlinkUrl;
use Shlinkio\Shlink\Importer\Sources\ImportSources;
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
        $source = $params['source'];
        $iterable = $this->batchHelper->wrapIterable($shlinkUrls, $source === ImportSources::SHLINK ? 10 : 100);

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
            $longUrl = $importedUrl->longUrl();

            try {
                $shortUrlImporting = $this->resolveShortUrl($importedUrl, $importShortCodes, $skipOnShortCodeConflict);
            } catch (NonUniqueSlugException $e) {
                $io->text(sprintf('%s: <fg=red>Error</>', $longUrl));
                continue;
            }

            $resultMessage = $shortUrlImporting->importVisits($importedUrl->visits(), $this->em);
            $io->text(sprintf('%s: %s', $longUrl, $resultMessage));
        }
    }

    private function resolveShortUrl(
        ImportedShlinkUrl $importedUrl,
        bool $importShortCodes,
        callable $skipOnShortCodeConflict
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
}
