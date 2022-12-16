<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\ShortUrl\Model;

use Shlinkio\Shlink\Common\Util\DateRange;
use Shlinkio\Shlink\Core\Exception\ValidationException;
use Shlinkio\Shlink\Core\Model\Ordering;
use Shlinkio\Shlink\Core\ShortUrl\Model\Validation\ShortUrlsParamsInputFilter;

use function Shlinkio\Shlink\Common\buildDateRange;
use function Shlinkio\Shlink\Core\normalizeOptionalDate;

final class ShortUrlsParams
{
    public const DEFAULT_ITEMS_PER_PAGE = 10;

    private function __construct(
        public readonly int $page,
        public readonly int $itemsPerPage,
        public readonly ?string $searchTerm,
        public readonly array $tags,
        public readonly Ordering $orderBy,
        public readonly ?DateRange $dateRange,
        public readonly bool $excludeMaxVisitsReached,
        public readonly bool $excludePastValidUntil,
        public readonly TagsMode $tagsMode = TagsMode::ANY,
    ) {
    }

    public static function emptyInstance(): self
    {
        return self::fromRawData([]);
    }

    /**
     * @throws ValidationException
     */
    public static function fromRawData(array $query): self
    {
        $inputFilter = new ShortUrlsParamsInputFilter($query);
        if (! $inputFilter->isValid()) {
            throw ValidationException::fromInputFilter($inputFilter);
        }

        return new self(
            page: (int) ($inputFilter->getValue(ShortUrlsParamsInputFilter::PAGE) ?? 1),
            itemsPerPage: (int) (
                $inputFilter->getValue(ShortUrlsParamsInputFilter::ITEMS_PER_PAGE) ?? self::DEFAULT_ITEMS_PER_PAGE
            ),
            searchTerm: $inputFilter->getValue(ShortUrlsParamsInputFilter::SEARCH_TERM),
            tags: (array) $inputFilter->getValue(ShortUrlsParamsInputFilter::TAGS),
            orderBy: Ordering::fromTuple($inputFilter->getValue(ShortUrlsParamsInputFilter::ORDER_BY)),
            dateRange: buildDateRange(
                normalizeOptionalDate($inputFilter->getValue(ShortUrlsParamsInputFilter::START_DATE)),
                normalizeOptionalDate($inputFilter->getValue(ShortUrlsParamsInputFilter::END_DATE)),
            ),
            excludeMaxVisitsReached: $inputFilter->getValue(ShortUrlsParamsInputFilter::EXCLUDE_MAX_VISITS_REACHED),
            excludePastValidUntil: $inputFilter->getValue(ShortUrlsParamsInputFilter::EXCLUDE_PAST_VALID_UNTIL),
            tagsMode: self::resolveTagsMode($inputFilter->getValue(ShortUrlsParamsInputFilter::TAGS_MODE)),
        );
    }

    private static function resolveTagsMode(?string $rawTagsMode): TagsMode
    {
        if ($rawTagsMode === null) {
            return TagsMode::ANY;
        }

        return TagsMode::tryFrom($rawTagsMode) ?? TagsMode::ANY;
    }
}
