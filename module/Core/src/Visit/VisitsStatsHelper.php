<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Visit;

use Doctrine\ORM\EntityManagerInterface;
use Pagerfanta\Adapter\AdapterInterface;
use Shlinkio\Shlink\Common\Paginator\Paginator;
use Shlinkio\Shlink\Core\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Entity\Tag;
use Shlinkio\Shlink\Core\Entity\Visit;
use Shlinkio\Shlink\Core\Exception\ShortUrlNotFoundException;
use Shlinkio\Shlink\Core\Exception\TagNotFoundException;
use Shlinkio\Shlink\Core\Model\ShortUrlIdentifier;
use Shlinkio\Shlink\Core\Model\VisitsParams;
use Shlinkio\Shlink\Core\Repository\ShortUrlRepositoryInterface;
use Shlinkio\Shlink\Core\Repository\TagRepository;
use Shlinkio\Shlink\Core\Repository\VisitRepository;
use Shlinkio\Shlink\Core\Repository\VisitRepositoryInterface;
use Shlinkio\Shlink\Core\Visit\Model\VisitsStats;
use Shlinkio\Shlink\Core\Visit\Paginator\Adapter\NonOrphanVisitsPaginatorAdapter;
use Shlinkio\Shlink\Core\Visit\Paginator\Adapter\OrphanVisitsPaginatorAdapter;
use Shlinkio\Shlink\Core\Visit\Paginator\Adapter\ShortUrlVisitsPaginatorAdapter;
use Shlinkio\Shlink\Core\Visit\Paginator\Adapter\TagVisitsPaginatorAdapter;
use Shlinkio\Shlink\Core\Visit\Persistence\VisitsCountFiltering;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

class VisitsStatsHelper implements VisitsStatsHelperInterface
{
    public function __construct(private EntityManagerInterface $em)
    {
    }

    public function getVisitsStats(?ApiKey $apiKey = null): VisitsStats
    {
        /** @var VisitRepository $visitsRepo */
        $visitsRepo = $this->em->getRepository(Visit::class);

        return new VisitsStats(
            $visitsRepo->countNonOrphanVisits(VisitsCountFiltering::withApiKey($apiKey)),
            $visitsRepo->countOrphanVisits(new VisitsCountFiltering()),
        );
    }

    /**
     * @return Visit[]|Paginator
     * @throws ShortUrlNotFoundException
     */
    public function visitsForShortUrl(
        ShortUrlIdentifier $identifier,
        VisitsParams $params,
        ?ApiKey $apiKey = null,
    ): Paginator {
        /** @var ShortUrlRepositoryInterface $repo */
        $repo = $this->em->getRepository(ShortUrl::class);
        if (! $repo->shortCodeIsInUse($identifier, $apiKey?->spec())) {
            throw ShortUrlNotFoundException::fromNotFound($identifier);
        }

        /** @var VisitRepositoryInterface $repo */
        $repo = $this->em->getRepository(Visit::class);

        return $this->createPaginator(
            new ShortUrlVisitsPaginatorAdapter($repo, $identifier, $params, $apiKey),
            $params,
        );
    }

    /**
     * @return Visit[]|Paginator
     * @throws TagNotFoundException
     */
    public function visitsForTag(string $tag, VisitsParams $params, ?ApiKey $apiKey = null): Paginator
    {
        /** @var TagRepository $tagRepo */
        $tagRepo = $this->em->getRepository(Tag::class);
        if (! $tagRepo->tagExists($tag, $apiKey)) {
            throw TagNotFoundException::fromTag($tag);
        }

        /** @var VisitRepositoryInterface $repo */
        $repo = $this->em->getRepository(Visit::class);

        return $this->createPaginator(new TagVisitsPaginatorAdapter($repo, $tag, $params, $apiKey), $params);
    }

    /**
     * @return Visit[]|Paginator
     */
    public function orphanVisits(VisitsParams $params): Paginator
    {
        /** @var VisitRepositoryInterface $repo */
        $repo = $this->em->getRepository(Visit::class);

        return $this->createPaginator(new OrphanVisitsPaginatorAdapter($repo, $params), $params);
    }

    public function nonOrphanVisits(VisitsParams $params, ?ApiKey $apiKey = null): Paginator
    {
        /** @var VisitRepositoryInterface $repo */
        $repo = $this->em->getRepository(Visit::class);

        return $this->createPaginator(new NonOrphanVisitsPaginatorAdapter($repo, $params, $apiKey), $params);
    }

    private function createPaginator(AdapterInterface $adapter, VisitsParams $params): Paginator
    {
        $paginator = new Paginator($adapter);
        $paginator->setMaxPerPage($params->getItemsPerPage())
                  ->setCurrentPage($params->getPage());

        return $paginator;
    }
}
