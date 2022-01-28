<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Visit\Paginator\Adapter;

use Shlinkio\Shlink\Core\Model\VisitsParams;
use Shlinkio\Shlink\Core\Paginator\Adapter\AbstractCacheableCountPaginatorAdapter;
use Shlinkio\Shlink\Core\Repository\VisitRepositoryInterface;
use Shlinkio\Shlink\Core\Visit\Persistence\VisitsCountFiltering;
use Shlinkio\Shlink\Core\Visit\Persistence\VisitsListFiltering;

class OrphanVisitsPaginatorAdapter extends AbstractCacheableCountPaginatorAdapter
{
    public function __construct(private VisitRepositoryInterface $repo, private VisitsParams $params)
    {
    }

    protected function doCount(): int
    {
        return $this->repo->countOrphanVisits(new VisitsCountFiltering(
            $this->params->getDateRange(),
            $this->params->excludeBots(),
        ));
    }

    public function getSlice(int $offset, int $length): iterable
    {
        return $this->repo->findOrphanVisits(new VisitsListFiltering(
            $this->params->getDateRange(),
            $this->params->excludeBots(),
            null,
            $length,
            $offset,
        ));
    }
}
