<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Tag\Model;

use Shlinkio\Shlink\Core\Model\Ordering;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

final class TagsListFiltering
{
    public function __construct(
        public readonly int|null $limit = null,
        public readonly int|null $offset = null,
        public readonly string|null $searchTerm = null,
        public readonly Ordering|null $orderBy = null,
        public readonly ApiKey|null $apiKey = null,
    ) {
    }

    public static function fromRangeAndParams(int $limit, int $offset, TagsParams $params, ApiKey|null $apiKey): self
    {
        return new self($limit, $offset, $params->searchTerm, $params->orderBy, $apiKey);
    }
}
