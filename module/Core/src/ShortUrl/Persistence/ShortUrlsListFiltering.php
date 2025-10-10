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
    /**
     * @inheritDoc
     */
    public function __construct(
        public readonly int|null $limit = null,
        public readonly int|null $offset = null,
        public readonly Ordering $orderBy = new Ordering(),
        string|null $searchTerm = null,
        array $tags = [],
        TagsMode $tagsMode = TagsMode::ANY,
        DateRange|null $dateRange = null,
        bool $excludeMaxVisitsReached = false,
        bool $excludePastValidUntil = false,
        ApiKey|null $apiKey = null,
        string|null $defaultDomain = null,
        string|null $domain = null,
        array $excludeTags = [],
        TagsMode $excludeTagsMode = TagsMode::ANY,
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
            $domain,
            $excludeTags,
            $excludeTagsMode,
        );
    }

    public static function fromLimitsAndParams(
        int $limit,
        int $offset,
        ShortUrlsParams $params,
        ApiKey|null $apiKey,
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
            $params->domain,
            $params->excludeTags,
            $params->excludeTagsMode,
        );
    }
}
