<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Tag\Model;

use Shlinkio\Shlink\Core\Model\AbstractInfinitePaginableListParams;
use Shlinkio\Shlink\Core\Model\Ordering;

final class TagsParams extends AbstractInfinitePaginableListParams
{
    private function __construct(
        public readonly string|null $searchTerm,
        public readonly Ordering $orderBy,
        int|null $page,
        int|null $itemsPerPage,
    ) {
        parent::__construct($page, $itemsPerPage);
    }

    public static function fromRawData(array $query): self
    {
        return new self(
            $query['searchTerm'] ?? null,
            Ordering::fromOptionalString($query['orderBy'] ?? null),
            isset($query['page']) ? (int) $query['page'] : null,
            isset($query['itemsPerPage']) ? (int) $query['itemsPerPage'] : null,
        );
    }
}
