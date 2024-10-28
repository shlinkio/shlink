<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Visit\Paginator\Adapter;

use Shlinkio\Shlink\Core\Paginator\Adapter\AbstractCacheableCountPaginatorAdapter;
use Shlinkio\Shlink\Core\Visit\Entity\Visit;
use Shlinkio\Shlink\Core\Visit\Model\OrphanVisitsParams;
use Shlinkio\Shlink\Core\Visit\Persistence\OrphanVisitsCountFiltering;
use Shlinkio\Shlink\Core\Visit\Persistence\OrphanVisitsListFiltering;
use Shlinkio\Shlink\Core\Visit\Repository\VisitRepositoryInterface;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

/** @extends AbstractCacheableCountPaginatorAdapter<Visit> */
class OrphanVisitsPaginatorAdapter extends AbstractCacheableCountPaginatorAdapter
{
    public function __construct(
        private readonly VisitRepositoryInterface $repo,
        private readonly OrphanVisitsParams $params,
        private readonly ApiKey|null $apiKey,
    ) {
    }

    protected function doCount(): int
    {
        return $this->repo->countOrphanVisits(new OrphanVisitsCountFiltering(
            dateRange: $this->params->dateRange,
            excludeBots: $this->params->excludeBots,
            apiKey: $this->apiKey,
            type: $this->params->type,
        ));
    }

    public function getSlice(int $offset, int $length): iterable
    {
        return $this->repo->findOrphanVisits(new OrphanVisitsListFiltering(
            dateRange: $this->params->dateRange,
            excludeBots: $this->params->excludeBots,
            apiKey: $this->apiKey,
            type: $this->params->type,
            limit: $length,
            offset: $offset,
        ));
    }
}
