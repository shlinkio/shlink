<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Model;

use Shlinkio\Shlink\Common\Util\DateRange;
use Shlinkio\Shlink\Core\Exception\ValidationException;
use Shlinkio\Shlink\Core\Validation\ShortUrlsParamsInputFilter;

use function Shlinkio\Shlink\Core\parseDateField;

final class ShortUrlsParams
{
    public const DEFAULT_ITEMS_PER_PAGE = 10;

    private int $page;
    private ?string $searchTerm;
    private array $tags;
    private ShortUrlsOrdering $orderBy;
    private ?DateRange $dateRange;
    private ?int $itemsPerPage = null;

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
        $this->dateRange = new DateRange(
            parseDateField($inputFilter->getValue(ShortUrlsParamsInputFilter::START_DATE)),
            parseDateField($inputFilter->getValue(ShortUrlsParamsInputFilter::END_DATE)),
        );
        $this->orderBy = ShortUrlsOrdering::fromRawData($query);
        $this->itemsPerPage = (int) (
            $inputFilter->getValue(ShortUrlsParamsInputFilter::ITEMS_PER_PAGE) ?? self::DEFAULT_ITEMS_PER_PAGE
        );
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

    public function orderBy(): ShortUrlsOrdering
    {
        return $this->orderBy;
    }

    public function dateRange(): ?DateRange
    {
        return $this->dateRange;
    }
}
