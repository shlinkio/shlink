<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Importer;

use Doctrine\ORM\EntityManagerInterface;
use Shlinkio\Shlink\Core\Domain\Resolver\DomainResolverInterface;
use Shlinkio\Shlink\Core\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Util\TagManagerTrait;
use Shlinkio\Shlink\Importer\ImportedLinksProcessorInterface;
use Shlinkio\Shlink\Importer\Model\ShlinkUrl;

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
     * @param ShlinkUrl[] $shlinkUrls
     */
    public function process(iterable $shlinkUrls, string $source, array $params): void
    {
        $importShortCodes = $params['import_short_codes'];
        $count = 0;
        $persistBlock = 100;

        foreach ($shlinkUrls as $url) {
            $count++;

            $shortUrl = ShortUrl::fromImport($url, $source, $importShortCodes, $this->domainResolver);
            $shortUrl->setTags($this->tagNamesToEntities($this->em, $url->tags()));

            // TODO Handle errors while creating short URLs, to avoid making the whole process fail
            $this->em->persist($shortUrl);

            // Flush and clear after X iterations
            if ($count % $persistBlock === 0) {
                $this->em->flush();
                $this->em->clear();
            }
        }

        $this->em->flush();
        $this->em->clear();
    }
}
