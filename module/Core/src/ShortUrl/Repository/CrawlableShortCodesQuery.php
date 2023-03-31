<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\ShortUrl\Repository;

use Happyr\DoctrineSpecification\Repository\EntitySpecificationRepository;
use Shlinkio\Shlink\Core\ShortUrl\Entity\ShortUrl;

class CrawlableShortCodesQuery extends EntitySpecificationRepository implements CrawlableShortCodesQueryInterface
{
    /**
     * @return iterable<string>
     */
    public function __invoke(): iterable
    {
        $blockSize = 1000;
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('DISTINCT s.shortCode')
           ->from(ShortUrl::class, 's')
           ->where($qb->expr()->eq('s.crawlable', ':crawlable'))
           ->setParameter('crawlable', true)
           ->setMaxResults($blockSize)
           ->orderBy('s.shortCode');

        $page = 0;
        do {
            $qbClone = (clone $qb)->setFirstResult($blockSize * $page);
            $iterator = $qbClone->getQuery()->toIterable();
            $resultsFound = false;
            $page++;

            foreach ($iterator as ['shortCode' => $shortCode]) {
                $resultsFound = true;
                yield $shortCode;
            }
        } while ($resultsFound);
    }
}
