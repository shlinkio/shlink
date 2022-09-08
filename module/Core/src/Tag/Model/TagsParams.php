<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Tag\Model;

use Shlinkio\Shlink\Core\Model\AbstractInfinitePaginableListParams;
use Shlinkio\Shlink\Core\Model\Ordering;

use function Shlinkio\Shlink\Common\parseOrderBy;

final class TagsParams extends AbstractInfinitePaginableListParams
{
    private function __construct(
        public readonly ?string $searchTerm,
        public readonly Ordering $orderBy,
        /** @deprecated */
        public readonly bool $withStats,
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
}
