<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\ShortUrl\Persistence;

use Shlinkio\Shlink\Common\Util\DateRange;
use Shlinkio\Shlink\Core\Model\Ordering;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlsParams;
use Shlinkio\Shlink\Core\ShortUrl\Model\TagsMode;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

class ShortUrlsListFiltering extends ShortUrlsCountFiltering
{
    public function __construct(
        public readonly ?int $limit,
        public readonly ?int $offset,
        public readonly Ordering $orderBy,
        ?string $searchTerm = null,
        array $tags = [],
        ?TagsMode $tagsMode = null,
        ?DateRange $dateRange = null,
        bool $excludeMaxVisitsReached = false,
        bool $excludePastValidUntil = false,
        ?ApiKey $apiKey = null,
        ?string $defaultDomain = null,
    ) {
        parent::__construct(
            $searchTerm,
            $tags,
            $tagsMode,
            $dateRange,
            $excludeMaxVisitsReached,
            $excludePastValidUntil,
            $apiKey,
            $defaultDomain,
        );
    }

    public static function fromLimitsAndParams(
        int $limit,
        int $offset,
        ShortUrlsParams $params,
        ?ApiKey $apiKey,
        string $defaultDomain,
    ): self {
        return new self(
            $limit,
            $offset,
            $params->orderBy,
            $params->searchTerm,
            $params->tags,
            $params->tagsMode,
            $params->dateRange,
            $params->excludeMaxVisitsReached,
            $params->excludePastValidUntil,
            $apiKey,
            $defaultDomain,
        );
    }
}
