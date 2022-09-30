<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\ShortUrl\Model;

use Shlinkio\Shlink\Common\Util\DateRange;
use Shlinkio\Shlink\Core\Exception\ValidationException;
use Shlinkio\Shlink\Core\Model\Ordering;
use Shlinkio\Shlink\Core\ShortUrl\Model\Validation\ShortUrlsParamsInputFilter;

use function Shlinkio\Shlink\Common\buildDateRange;
use function Shlinkio\Shlink\Core\normalizeDate;

final class ShortUrlsParams
{
    public const ORDERABLE_FIELDS = ['longUrl', 'shortCode', 'dateCreated', 'title', 'visits'];
    public const DEFAULT_ITEMS_PER_PAGE = 10;

    private int $page;
    private int $itemsPerPage;
    private ?string $searchTerm;
    private array $tags;
    private TagsMode $tagsMode = TagsMode::ANY;
    private Ordering $orderBy;
    private ?DateRange $dateRange;

    private function __construct()
    {
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
        $instance = new self();
        $instance->validateAndInit($query);

        return $instance;
    }

    /**
     * @throws ValidationException
     */
    private function validateAndInit(array $query): void
    {
        $inputFilter = new ShortUrlsParamsInputFilter($query);
        if (! $inputFilter->isValid()) {
            throw ValidationException::fromInputFilter($inputFilter);
        }

        $this->page = (int) ($inputFilter->getValue(ShortUrlsParamsInputFilter::PAGE) ?? 1);
        $this->searchTerm = $inputFilter->getValue(ShortUrlsParamsInputFilter::SEARCH_TERM);
        $this->tags = (array) $inputFilter->getValue(ShortUrlsParamsInputFilter::TAGS);
        $this->dateRange = buildDateRange(
            normalizeDate($inputFilter->getValue(ShortUrlsParamsInputFilter::START_DATE)),
            normalizeDate($inputFilter->getValue(ShortUrlsParamsInputFilter::END_DATE)),
        );
        $this->orderBy = Ordering::fromTuple($inputFilter->getValue(ShortUrlsParamsInputFilter::ORDER_BY));
        $this->itemsPerPage = (int) (
            $inputFilter->getValue(ShortUrlsParamsInputFilter::ITEMS_PER_PAGE) ?? self::DEFAULT_ITEMS_PER_PAGE
        );
        $this->tagsMode = $this->resolveTagsMode($inputFilter->getValue(ShortUrlsParamsInputFilter::TAGS_MODE));
    }

    private function resolveTagsMode(?string $rawTagsMode): TagsMode
    {
        if ($rawTagsMode === null) {
            return TagsMode::ANY;
        }

        return TagsMode::tryFrom($rawTagsMode) ?? TagsMode::ANY;
    }

    public function page(): int
    {
        return $this->page;
    }

    public function itemsPerPage(): int
    {
        return $this->itemsPerPage;
    }

    public function searchTerm(): ?string
    {
        return $this->searchTerm;
    }

    public function tags(): array
    {
        return $this->tags;
    }

    public function orderBy(): Ordering
    {
        return $this->orderBy;
    }

    public function dateRange(): ?DateRange
    {
        return $this->dateRange;
    }

    public function tagsMode(): TagsMode
    {
        return $this->tagsMode;
    }
}
