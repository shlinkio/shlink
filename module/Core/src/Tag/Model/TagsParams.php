<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Tag\Model;

use Shlinkio\Shlink\Core\Model\AbstractInfinitePaginableListParams;
use Shlinkio\Shlink\Core\Model\Ordering;

use function Shlinkio\Shlink\Common\parseOrderBy;

final class TagsParams extends AbstractInfinitePaginableListParams
{
    private function __construct(
        private ?string $searchTerm,
        private Ordering $orderBy,
        private bool $withStats,
        ?int $page,
        ?int $itemsPerPage,
    ) {
        parent::__construct($page, $itemsPerPage);
    }

    public static function fromRawData(array $query): self
    {
        return new self(
            $query['searchTerm'] ?? null,
            Ordering::fromTuple(isset($query['orderBy']) ? parseOrderBy($query['orderBy']) : [null, null]),
            ($query['withStats'] ?? null) === 'true',
            isset($query['page']) ? (int) $query['page'] : null,
            isset($query['itemsPerPage']) ? (int) $query['itemsPerPage'] : null,
        );
    }

    public function searchTerm(): ?string
    {
        return $this->searchTerm;
    }

    public function orderBy(): Ordering
    {
        return $this->orderBy;
    }

    public function withStats(): bool
    {
        return $this->withStats;
    }
}
