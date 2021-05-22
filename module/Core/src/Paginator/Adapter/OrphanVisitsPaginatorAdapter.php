<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Paginator\Adapter;

use Shlinkio\Shlink\Core\Model\VisitsParams;
use Shlinkio\Shlink\Core\Repository\VisitRepositoryInterface;
use Shlinkio\Shlink\Core\Visit\Persistence\VisitsCountFiltering;
use Shlinkio\Shlink\Core\Visit\Persistence\VisitsListFiltering;

class OrphanVisitsPaginatorAdapter extends AbstractCacheableCountPaginatorAdapter
{
    private VisitRepositoryInterface $repo;
    private VisitsParams $params;

    public function __construct(VisitRepositoryInterface $repo, VisitsParams $params)
    {
        $this->repo = $repo;
        $this->params = $params;
    }

    protected function doCount(): int
    {
        return $this->repo->countOrphanVisits(new VisitsCountFiltering(
            $this->params->getDateRange(),
            $this->params->excludeBots(),
        ));
    }

    public function getSlice($offset, $length): iterable // phpcs:ignore
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
