<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Paginator\Adapter;

use Laminas\Paginator\Adapter\AdapterInterface;
use Shlinkio\Shlink\Core\Model\ShortUrlsParams;
use Shlinkio\Shlink\Core\Repository\ShortUrlRepositoryInterface;

class ShortUrlRepositoryAdapter implements AdapterInterface
{
    public const ITEMS_PER_PAGE = 10;

    private ShortUrlRepositoryInterface $repository;
    private ShortUrlsParams $params;

    public function __construct(ShortUrlRepositoryInterface $repository, ShortUrlsParams $params)
    {
        $this->repository = $repository;
        $this->params = $params;
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
            $this->params->searchTerm(),
            $this->params->tags(),
            $this->params->orderBy(),
            $this->params->dateRange(),
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
        return $this->repository->countList(
            $this->params->searchTerm(),
            $this->params->tags(),
            $this->params->dateRange(),
        );
    }
}
