<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Importer;

use Doctrine\ORM\EntityManagerInterface;
use Shlinkio\Shlink\Core\Domain\Resolver\DomainResolverInterface;
use Shlinkio\Shlink\Core\Entity\ShortUrl;
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
            // Skip already imported URLs
            if ($shortUrlRepo->importedUrlExists($url, $importShortCodes)) {
                $io->text(sprintf('%s: <comment>Skipped</comment>', $url->longUrl()));
                continue;
            }

            $shortUrl = ShortUrl::fromImport($url, $importShortCodes, $this->domainResolver);
            $shortUrl->setTags($this->tagNamesToEntities($this->em, $url->tags()));


            // TODO Handle errors while creating short URLs, to avoid making the whole process fail
            //        * Duplicated short code
            $this->em->persist($shortUrl);

            $io->text(sprintf('%s: <info>Imported</info>', $url->longUrl()));
        }
    }
}
