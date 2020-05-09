<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Paginator\Adapter;

use Laminas\Paginator\Adapter\AdapterInterface;
use Shlinkio\Shlink\Core\Model\VisitsParams;
use Shlinkio\Shlink\Core\Repository\VisitRepositoryInterface;

class VisitsForTagPaginatorAdapter implements AdapterInterface
{
    private VisitRepositoryInterface $visitRepository;
    private string $tag;
    private VisitsParams $params;

    private ?int $count = null;

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

    public function count(): int
    {
        // Since a new adapter instance is created every time visits are fetched, it is reasonably safe to internally
        // cache the count value.
        // The reason it is cached is because the Paginator is actually calling the method twice.
        // An inconsistent value could be returned if between the first call and the second one, a new visit is created.
        // However, it's almost instant, and then the adapter instance is discarded immediately after.

        if ($this->count !== null) {
            return $this->count;
        }

        return $this->count = $this->visitRepository->countVisitsByTag($this->tag, $this->params->getDateRange());
    }
}
