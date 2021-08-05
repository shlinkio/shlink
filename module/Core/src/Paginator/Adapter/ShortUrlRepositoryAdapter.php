<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Paginator\Adapter;

use Pagerfanta\Adapter\AdapterInterface;
use Shlinkio\Shlink\Core\Model\ShortUrlsParams;
use Shlinkio\Shlink\Core\Repository\ShortUrlRepositoryInterface;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

class ShortUrlRepositoryAdapter implements AdapterInterface
{
    public function __construct(
        private ShortUrlRepositoryInterface $repository,
        private ShortUrlsParams $params,
        private ?ApiKey $apiKey,
    ) {
    }

    public function getSlice($offset, $length): array // phpcs:ignore
    {
        return $this->repository->findList(
            $length,
            $offset,
            $this->params->searchTerm(),
            $this->params->tags(),
            $this->params->orderBy(),
            $this->params->dateRange(),
            $this->apiKey?->spec(),
        );
    }

    public function getNbResults(): int
    {
        return $this->repository->countList(
            $this->params->searchTerm(),
            $this->params->tags(),
            $this->params->dateRange(),
            $this->apiKey?->spec(),
        );
    }
}
