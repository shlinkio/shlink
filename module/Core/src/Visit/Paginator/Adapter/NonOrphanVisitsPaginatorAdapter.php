<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Visit\Paginator\Adapter;

use Shlinkio\Shlink\Core\Model\VisitsParams;
use Shlinkio\Shlink\Core\Paginator\Adapter\AbstractCacheableCountPaginatorAdapter;
use Shlinkio\Shlink\Core\Repository\VisitRepositoryInterface;
use Shlinkio\Shlink\Core\Visit\Persistence\VisitsCountFiltering;
use Shlinkio\Shlink\Core\Visit\Persistence\VisitsListFiltering;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

class NonOrphanVisitsPaginatorAdapter extends AbstractCacheableCountPaginatorAdapter
{
    public function __construct(
        private VisitRepositoryInterface $repo,
        private VisitsParams $params,
        private ?ApiKey $apiKey,
    ) {
    }

    protected function doCount(): int
    {
        return $this->repo->countNonOrphanVisits(new VisitsCountFiltering(
            $this->params->getDateRange(),
            $this->params->excludeBots(),
            $this->apiKey,
        ));
    }

    public function getSlice(int $offset, int $length): iterable
    {
        return $this->repo->findNonOrphanVisits(new VisitsListFiltering(
            $this->params->getDateRange(),
            $this->params->excludeBots(),
            $this->apiKey,
            $length,
            $offset,
        ));
    }
}
