<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Tag\Model;

use Shlinkio\Shlink\Core\Model\Ordering;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

final class TagsListFiltering
{
    public function __construct(
        public readonly ?int $limit = null,
        public readonly ?int $offset = null,
        public readonly ?string $searchTerm = null,
        public readonly ?Ordering $orderBy = null,
        public readonly ?ApiKey $apiKey = null,
    ) {
    }

    public static function fromRangeAndParams(int $limit, int $offset, TagsParams $params, ?ApiKey $apiKey): self
    {
        return new self($limit, $offset, $params->searchTerm, $params->orderBy, $apiKey);
    }
}
