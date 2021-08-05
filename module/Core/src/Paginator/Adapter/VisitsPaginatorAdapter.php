<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Paginator\Adapter;

use Happyr\DoctrineSpecification\Specification\Specification;
use Shlinkio\Shlink\Core\Model\ShortUrlIdentifier;
use Shlinkio\Shlink\Core\Model\VisitsParams;
use Shlinkio\Shlink\Core\Repository\VisitRepositoryInterface;
use Shlinkio\Shlink\Core\Visit\Persistence\VisitsCountFiltering;
use Shlinkio\Shlink\Core\Visit\Persistence\VisitsListFiltering;

class VisitsPaginatorAdapter extends AbstractCacheableCountPaginatorAdapter
{
    public function __construct(
        private VisitRepositoryInterface $visitRepository,
        private ShortUrlIdentifier $identifier,
        private VisitsParams $params,
        private ?Specification $spec,
    ) {
    }

    public function getSlice($offset, $length): array // phpcs:ignore
    {
        return $this->visitRepository->findVisitsByShortCode(
            $this->identifier,
            new VisitsListFiltering(
                $this->params->getDateRange(),
                $this->params->excludeBots(),
                $this->spec,
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
                $this->spec,
            ),
        );
    }
}
