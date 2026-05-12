<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\ShortUrl\Model;

use DateTimeInterface;
use Shlinkio\Shlink\Common\ObjectMapper\TagsConverter;
use Shlinkio\Shlink\Common\Util\DateRange;
use Shlinkio\Shlink\Core\Model\Ordering;
use Shlinkio\Shlink\Core\ObjectMapper\OrderingConverter;

use function array_unique;
use function Shlinkio\Shlink\Common\buildDateRange;
use function Shlinkio\Shlink\Common\normalizeOptionalDate;

/**
 * Represents all the params that can be used to filter a list of short URLs
 */
final readonly class ShortUrlsParams
{
    public const int DEFAULT_ITEMS_PER_PAGE = 10;

    public DateRange|null $dateRange;
    /** @var string[] */
    public array $tags;
    /** @var string[] */
    public array $excludeTags;

    /**
     * @param string[] $tags
     * @param string[] $excludeTags
     */
    public function __construct(
        public int $page = 1,
        public int $itemsPerPage = self::DEFAULT_ITEMS_PER_PAGE,
        public string|null $searchTerm = null,
        #[TagsConverter]
        array $tags = [],
        #[OrderingConverter(OrderableField::class)]
        public Ordering $orderBy = new Ordering(),
        DateTimeInterface|string|null $startDate = null,
        DateTimeInterface|string|null $endDate = null,
        public bool $excludeMaxVisitsReached = false,
        public bool $excludePastValidUntil = false,
        public TagsMode $tagsMode = TagsMode::ANY,
        public string|null $domain = null,
        #[TagsConverter]
        array $excludeTags = [],
        public TagsMode $excludeTagsMode = TagsMode::ANY,
        public string|null $apiKeyName = null,
    ) {
        $this->dateRange = buildDateRange(
            normalizeOptionalDate($startDate),
            normalizeOptionalDate($endDate),
        );

        // FIXME When using shlink-common, TagsConverter implicitly does an array_unique.
        $this->tags = array_unique($tags);
        $this->excludeTags = array_unique($excludeTags);
    }
}
