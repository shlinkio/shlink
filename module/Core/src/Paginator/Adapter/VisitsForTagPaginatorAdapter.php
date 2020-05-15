<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Paginator\Adapter;

use Shlinkio\Shlink\Core\Model\VisitsParams;
use Shlinkio\Shlink\Core\Repository\VisitRepositoryInterface;

class VisitsForTagPaginatorAdapter extends AbstractCacheableCountPaginatorAdapter
{
    private VisitRepositoryInterface $visitRepository;
    private string $tag;
    private VisitsParams $params;

    public function __construct(VisitRepositoryInterface $visitRepository, string $tag, VisitsParams $params)
    {
        $this->visitRepository = $visitRepository;
        $this->params = $params;
        $this->tag = $tag;
    }

    public function getItems($offset, $itemCountPerPage): array // phpcs:ignore
    {
        return $this->visitRepository->findVisitsByTag(
            $this->tag,
            $this->params->getDateRange(),
            $itemCountPerPage,
            $offset,
        );
    }

    protected function doCount(): int
    {
        return $this->visitRepository->countVisitsByTag($this->tag, $this->params->getDateRange());
    }
}
