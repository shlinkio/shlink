<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Visit\Paginator\Adapter;

use Shlinkio\Shlink\Core\Model\ShortUrlIdentifier;
use Shlinkio\Shlink\Core\Model\VisitsParams;
use Shlinkio\Shlink\Core\Paginator\Adapter\AbstractCacheableCountPaginatorAdapter;
use Shlinkio\Shlink\Core\Repository\VisitRepositoryInterface;
use Shlinkio\Shlink\Core\Visit\Persistence\VisitsCountFiltering;
use Shlinkio\Shlink\Core\Visit\Persistence\VisitsListFiltering;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

class ShortUrlVisitsPaginatorAdapter extends AbstractCacheableCountPaginatorAdapter
{
    public function __construct(
        private VisitRepositoryInterface $visitRepository,
        private ShortUrlIdentifier $identifier,
        private VisitsParams $params,
        private ?ApiKey $apiKey,
    ) {
    }

    public function getSlice(int $offset, int $length): iterable
    {
        return $this->visitRepository->findVisitsByShortCode(
            $this->identifier,
            new VisitsListFiltering(
                $this->params->getDateRange(),
                $this->params->excludeBots(),
                $this->apiKey,
                $length,
                $offset,
            ),
        );
    }

    protected function doCount(): int
    {
        return $this->visitRepository->countVisitsByShortCode(
            $this->identifier,
            new VisitsCountFiltering(
                $this->params->getDateRange(),
                $this->params->excludeBots(),
                $this->apiKey,
            ),
        );
    }
}
