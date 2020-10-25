<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Importer;

use Doctrine\ORM\EntityManagerInterface;
use Shlinkio\Shlink\Core\Domain\Resolver\DomainResolverInterface;
use Shlinkio\Shlink\Core\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Repository\ShortUrlRepository;
use Shlinkio\Shlink\Core\Repository\ShortUrlRepositoryInterface;
use Shlinkio\Shlink\Core\Util\DoctrineBatchIterator;
use Shlinkio\Shlink\Core\Util\TagManagerTrait;
use Shlinkio\Shlink\Importer\ImportedLinksProcessorInterface;
use Shlinkio\Shlink\Importer\Model\ImportedShlinkUrl;
use Symfony\Component\Console\Style\StyleInterface;

use function sprintf;

class ImportedLinksProcessor implements ImportedLinksProcessorInterface
{
    use TagManagerTrait;

    private EntityManagerInterface $em;
    private DomainResolverInterface $domainResolver;

    public function __construct(EntityManagerInterface $em, DomainResolverInterface $domainResolver)
    {
        $this->em = $em;
        $this->domainResolver = $domainResolver;
    }

    /**
     * @param iterable|ImportedShlinkUrl[] $shlinkUrls
     */
    public function process(StyleInterface $io, iterable $shlinkUrls, array $params): void
    {
        /** @var ShortUrlRepositoryInterface $shortUrlRepo */
        $shortUrlRepo = $this->em->getRepository(ShortUrl::class);
        $importShortCodes = $params['import_short_codes'];
        $iterable = new DoctrineBatchIterator($shlinkUrls, $this->em, 100);

        /** @var ImportedShlinkUrl $url */
        foreach ($iterable as $url) {
            $longUrl = $url->longUrl();

            // Skip already imported URLs
            if ($shortUrlRepo->importedUrlExists($url, $importShortCodes)) {
                $io->text(sprintf('%s: <comment>Skipped</comment>', $longUrl));
                continue;
            }

            $shortUrl = ShortUrl::fromImport($url, $importShortCodes, $this->domainResolver);
            $shortUrl->setTags($this->tagNamesToEntities($this->em, $url->tags()));

            if (! $this->handleShortcodeUniqueness($url, $shortUrl, $io, $importShortCodes)) {
                continue;
            }

            $this->em->persist($shortUrl);
            $io->text(sprintf('%s: <info>Imported</info>', $longUrl));
        }
    }

    private function handleShortcodeUniqueness(
        ImportedShlinkUrl $url,
        ShortUrl $shortUrl,
        StyleInterface $io,
        bool $importShortCodes
    ): bool {
        if ($this->ensureShortCodeUniqueness($shortUrl, $importShortCodes)) {
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
            $io->text(sprintf('%s: <comment>Skipped</comment>', $longUrl));
            return false;
        }

        return $this->handleShortcodeUniqueness($url, $shortUrl, $io, false);
    }

    private function ensureShortCodeUniqueness(ShortUrl $shortUrlToBeCreated, bool $hasCustomSlug): bool
    {
        $shortCode = $shortUrlToBeCreated->getShortCode();
        $domain = $shortUrlToBeCreated->getDomain();
        $domainAuthority = $domain !== null ? $domain->getAuthority() : null;

        /** @var ShortUrlRepository $repo */
        $repo = $this->em->getRepository(ShortUrl::class);
        $otherShortUrlsExist = $repo->shortCodeIsInUse($shortCode, $domainAuthority);

        if (! $otherShortUrlsExist) {
            return true;
        }

        if ($hasCustomSlug) {
            return false;
        }

        $shortUrlToBeCreated->regenerateShortCode();
        return $this->ensureShortCodeUniqueness($shortUrlToBeCreated, $hasCustomSlug);
    }
}
