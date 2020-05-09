<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Paginator\Adapter;

use Shlinkio\Shlink\Core\Model\ShortUrlIdentifier;
use Shlinkio\Shlink\Core\Model\VisitsParams;
use Shlinkio\Shlink\Core\Repository\VisitRepositoryInterface;

class VisitsPaginatorAdapter extends AbstractCacheableCountPaginatorAdapter
{
    private VisitRepositoryInterface $visitRepository;
    private ShortUrlIdentifier $identifier;
    private VisitsParams $params;

    public function __construct(
        VisitRepositoryInterface $visitRepository,
        ShortUrlIdentifier $identifier,
        VisitsParams $params
    ) {
        $this->visitRepository = $visitRepository;
        $this->params = $params;
        $this->identifier = $identifier;
    }

    public function getItems($offset, $itemCountPerPage): array // phpcs:ignore
    {
        return $this->visitRepository->findVisitsByShortCode(
            $this->identifier->shortCode(),
            $this->identifier->domain(),
            $this->params->getDateRange(),
            $itemCountPerPage,
            $offset,
        );
    }

    protected function doCount(): int
    {
        return $this->visitRepository->countVisitsByShortCode(
            $this->identifier->shortCode(),
            $this->identifier->domain(),
            $this->params->getDateRange(),
        );
    }
}
