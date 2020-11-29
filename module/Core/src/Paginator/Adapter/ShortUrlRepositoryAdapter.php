<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Paginator\Adapter;

use Laminas\Paginator\Adapter\AdapterInterface;
use Shlinkio\Shlink\Core\Model\ShortUrlsParams;
use Shlinkio\Shlink\Core\Repository\ShortUrlRepositoryInterface;

class ShortUrlRepositoryAdapter implements AdapterInterface
{
    private ShortUrlRepositoryInterface $repository;
    private ShortUrlsParams $params;

    public function __construct(ShortUrlRepositoryInterface $repository, ShortUrlsParams $params)
    {
        $this->repository = $repository;
        $this->params = $params;
    }

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

    public function count(): int
    {
        return $this->repository->countList(
            $this->params->searchTerm(),
            $this->params->tags(),
            $this->params->dateRange(),
        );
    }
}
