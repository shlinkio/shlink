<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Paginator\Adapter;

use Shlinkio\Shlink\Common\Util\DateRange;
use Shlinkio\Shlink\Core\Repository\ShortUrlRepositoryInterface;
use Zend\Paginator\Adapter\AdapterInterface;

use function strip_tags;
use function trim;

class ShortUrlRepositoryAdapter implements AdapterInterface
{
    public const ITEMS_PER_PAGE = 10;

    private ShortUrlRepositoryInterface $repository;
    private ?string $searchTerm;
    /** @var null|array|string */
    private $orderBy;
    private array $tags;
    private ?DateRange $dateRange;

    /**
     * @param string|array|null $orderBy
     */
    public function __construct(
        ShortUrlRepositoryInterface $repository,
        ?string $searchTerm = null,
        array $tags = [],
        $orderBy = null,
        ?DateRange $dateRange = null
    ) {
        $this->repository = $repository;
        $this->searchTerm = $searchTerm !== null ? trim(strip_tags($searchTerm)) : null;
        $this->orderBy = $orderBy;
        $this->tags = $tags;
        $this->dateRange = $dateRange;
    }

    /**
     * Returns a collection of items for a page.
     *
     * @param  int $offset Page offset
     * @param  int $itemCountPerPage Number of items per page
     */
    public function getItems($offset, $itemCountPerPage): array // phpcs:ignore
    {
        return $this->repository->findList(
            $itemCountPerPage,
            $offset,
            $this->searchTerm,
            $this->tags,
            $this->orderBy,
            $this->dateRange,
        );
    }

    /**
     * Count elements of an object
     * @link http://php.net/manual/en/countable.count.php
     * @return int The custom count as an integer.
     * </p>
     * <p>
     * The return value is cast to an integer.
     * @since 5.1.0
     */
    public function count(): int
    {
        return $this->repository->countList($this->searchTerm, $this->tags, $this->dateRange);
    }
}
