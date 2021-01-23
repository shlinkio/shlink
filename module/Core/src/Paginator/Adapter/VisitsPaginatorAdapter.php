<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Paginator\Adapter;

use Happyr\DoctrineSpecification\Specification\Specification;
use Shlinkio\Shlink\Core\Model\ShortUrlIdentifier;
use Shlinkio\Shlink\Core\Model\VisitsParams;
use Shlinkio\Shlink\Core\Repository\VisitRepositoryInterface;

class VisitsPaginatorAdapter extends AbstractCacheableCountPaginatorAdapter
{
    private VisitRepositoryInterface $visitRepository;
    private ShortUrlIdentifier $identifier;
    private VisitsParams $params;
    private ?Specification $spec;

    public function __construct(
        VisitRepositoryInterface $visitRepository,
        ShortUrlIdentifier $identifier,
        VisitsParams $params,
        ?Specification $spec
    ) {
        $this->visitRepository = $visitRepository;
        $this->params = $params;
        $this->identifier = $identifier;
        $this->spec = $spec;
    }

    public function getSlice($offset, $length): array // phpcs:ignore
    {
        return $this->visitRepository->findVisitsByShortCode(
            $this->identifier->shortCode(),
            $this->identifier->domain(),
            $this->params->getDateRange(),
            $length,
            $offset,
            $this->spec,
        );
    }

    protected function doCount(): int
    {
        return $this->visitRepository->countVisitsByShortCode(
            $this->identifier->shortCode(),
            $this->identifier->domain(),
            $this->params->getDateRange(),
            $this->spec,
        );
    }
}
