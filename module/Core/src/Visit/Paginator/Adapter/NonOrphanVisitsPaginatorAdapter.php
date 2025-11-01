<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Visit\Paginator\Adapter;

use Shlinkio\Shlink\Core\Paginator\Adapter\AbstractCacheableCountPaginatorAdapter;
use Shlinkio\Shlink\Core\Visit\Entity\Visit;
use Shlinkio\Shlink\Core\Visit\Model\WithDomainVisitsParams;
use Shlinkio\Shlink\Core\Visit\Persistence\WithDomainVisitsCountFiltering;
use Shlinkio\Shlink\Core\Visit\Persistence\WithDomainVisitsListFiltering;
use Shlinkio\Shlink\Core\Visit\Repository\VisitRepositoryInterface;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

/** @extends AbstractCacheableCountPaginatorAdapter<Visit> */
class NonOrphanVisitsPaginatorAdapter extends AbstractCacheableCountPaginatorAdapter
{
    public function __construct(
        private readonly VisitRepositoryInterface $repo,
        private readonly WithDomainVisitsParams $params,
        private readonly ApiKey|null $apiKey,
    ) {
    }

    protected function doCount(): int
    {
        return $this->repo->countNonOrphanVisits(new WithDomainVisitsCountFiltering(
            $this->params->dateRange,
            $this->params->excludeBots,
            $this->apiKey,
            $this->params->domain,
        ));
    }

    public function getSlice(int $offset, int $length): iterable
    {
        return $this->repo->findNonOrphanVisits(new WithDomainVisitsListFiltering(
            $this->params->dateRange,
            $this->params->excludeBots,
            $this->apiKey,
            $this->params->domain,
            $length,
            $offset,
        ));
    }
}
