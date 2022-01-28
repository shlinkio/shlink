<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Model;

use Shlinkio\Shlink\Common\Util\DateRange;
use Shlinkio\Shlink\Core\Exception\ValidationException;
use Shlinkio\Shlink\Core\Validation\ShortUrlsParamsInputFilter;

use function Shlinkio\Shlink\Common\buildDateRange;
use function Shlinkio\Shlink\Core\parseDateField;

final class ShortUrlsParams
{
    public const ORDERABLE_FIELDS = ['longUrl', 'shortCode', 'dateCreated', 'title', 'visits'];
    public const DEFAULT_ITEMS_PER_PAGE = 10;
    public const TAGS_MODE_ANY = 'any';
    public const TAGS_MODE_ALL = 'all';

    private int $page;
    private int $itemsPerPage;
    private ?string $searchTerm;
    private array $tags;
    /** @var self::TAGS_MODE_ANY|self::TAGS_MODE_ALL */
    private string $tagsMode = self::TAGS_MODE_ANY;
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
            parseDateField($inputFilter->getValue(ShortUrlsParamsInputFilter::START_DATE)),
            parseDateField($inputFilter->getValue(ShortUrlsParamsInputFilter::END_DATE)),
        );
        $this->orderBy = Ordering::fromTuple($inputFilter->getValue(ShortUrlsParamsInputFilter::ORDER_BY));
        $this->itemsPerPage = (int) (
            $inputFilter->getValue(ShortUrlsParamsInputFilter::ITEMS_PER_PAGE) ?? self::DEFAULT_ITEMS_PER_PAGE
        );
        $this->tagsMode = $inputFilter->getValue(ShortUrlsParamsInputFilter::TAGS_MODE) ?? self::TAGS_MODE_ANY;
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

    /**
     * @return self::TAGS_MODE_ANY|self::TAGS_MODE_ALL
     */
    public function tagsMode(): string
    {
        return $this->tagsMode;
    }
}
