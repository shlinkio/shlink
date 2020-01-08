<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Paginator\Adapter;

use Laminas\Paginator\Adapter\AdapterInterface;
use Shlinkio\Shlink\Core\Model\VisitsParams;
use Shlinkio\Shlink\Core\Repository\VisitRepositoryInterface;

class VisitsPaginatorAdapter implements AdapterInterface
{
    private VisitRepositoryInterface $visitRepository;
    private string $shortCode;
    private VisitsParams $params;

    public function __construct(VisitRepositoryInterface $visitRepository, string $shortCode, VisitsParams $params)
    {
        $this->visitRepository = $visitRepository;
        $this->shortCode = $shortCode;
        $this->params = $params;
    }

    public function getItems($offset, $itemCountPerPage): array // phpcs:ignore
    {
        return $this->visitRepository->findVisitsByShortCode(
            $this->shortCode,
            $this->params->getDateRange(),
            $itemCountPerPage,
            $offset,
        );
    }

    public function count(): int
    {
        return $this->visitRepository->countVisitsByShortCode($this->shortCode, $this->params->getDateRange());
    }
}
