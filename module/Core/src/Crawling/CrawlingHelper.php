<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Crawling;

use Doctrine\ORM\EntityManagerInterface;
use Shlinkio\Shlink\Core\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Repository\ShortUrlRepositoryInterface;

class CrawlingHelper implements CrawlingHelperInterface
{
    public function __construct(private EntityManagerInterface $em)
    {
    }

    public function listCrawlableShortCodes(): iterable
    {
        /** @var ShortUrlRepositoryInterface $repo */
        $repo = $this->em->getRepository(ShortUrl::class);
        yield from $repo->findCrawlableShortCodes();
    }
}
