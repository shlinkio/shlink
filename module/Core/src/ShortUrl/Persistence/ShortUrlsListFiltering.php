<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\ShortUrl\Persistence;

use Shlinkio\Shlink\Common\Util\DateRange;
use Shlinkio\Shlink\Core\Model\Ordering;
use Shlinkio\Shlink\Core\Model\ShortUrlsParams;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

class ShortUrlsListFiltering extends ShortUrlsCountFiltering
{
    public function __construct(
        private ?int $limit,
        private ?int $offset,
        private Ordering $orderBy,
        ?string $searchTerm = null,
        array $tags = [],
        ?string $tagsMode = null,
        ?DateRange $dateRange = null,
        ?ApiKey $apiKey = null,
    ) {
        parent::__construct($searchTerm, $tags, $tagsMode, $dateRange, $apiKey);
    }

    public static function fromLimitsAndParams(int $limit, int $offset, ShortUrlsParams $params, ?ApiKey $apiKey): self
    {
        return new self(
            $limit,
            $offset,
            $params->orderBy(),
            $params->searchTerm(),
            $params->tags(),
            $params->tagsMode(),
            $params->dateRange(),
            $apiKey,
        );
    }

    public function offset(): ?int
    {
        return $this->offset;
    }

    public function limit(): ?int
    {
        return $this->limit;
    }

    public function orderBy(): Ordering
    {
        return $this->orderBy;
    }
}
