<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\ShortUrl\Model;

use DateTimeInterface;
use Shlinkio\Shlink\Common\ObjectMapper\TagsConverter;
use Shlinkio\Shlink\Common\Util\DateRange;
use Shlinkio\Shlink\Core\Model\Ordering;
use Shlinkio\Shlink\Core\ObjectMapper\OrderingConverter;

use function Shlinkio\Shlink\Common\buildDateRange;
use function Shlinkio\Shlink\Common\normalizeOptionalDate;

/**
 * Represents all the params that can be used to filter a list of short URLs
 */
final readonly class ShortUrlsParams
{
    public const int DEFAULT_ITEMS_PER_PAGE = 10;

    public DateRange|null $dateRange;

    /**
     * @param positive-int $page
     * @param -1|positive-int $itemsPerPage
     * @param string[] $tags
     * @param string[] $excludeTags
     */
    public function __construct(
        public int $page = 1,
        public int $itemsPerPage = self::DEFAULT_ITEMS_PER_PAGE,
        public string|null $searchTerm = null,
        #[TagsConverter]
        public array $tags = [],
        #[OrderingConverter(OrderableField::class)]
        public Ordering $orderBy = new Ordering(),
        DateTimeInterface|string|null $startDate = null,
        DateTimeInterface|string|null $endDate = null,
        public bool $excludeMaxVisitsReached = false,
        public bool $excludePastValidUntil = false,
        public TagsMode $tagsMode = TagsMode::ANY,
        public string|null $domain = null,
        #[TagsConverter]
        public array $excludeTags = [],
        public TagsMode $excludeTagsMode = TagsMode::ANY,
        public string|null $apiKeyName = null,
    ) {
        $this->dateRange = buildDateRange(
            normalizeOptionalDate($startDate),
            normalizeOptionalDate($endDate),
        );
    }
}
