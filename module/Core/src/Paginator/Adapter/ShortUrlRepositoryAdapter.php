<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Paginator\Adapter;

use Happyr\DoctrineSpecification\Specification\Specification;
use Pagerfanta\Adapter\AdapterInterface;
use Shlinkio\Shlink\Core\Model\ShortUrlsParams;
use Shlinkio\Shlink\Core\Repository\ShortUrlRepositoryInterface;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

class ShortUrlRepositoryAdapter implements AdapterInterface
{
    private ShortUrlRepositoryInterface $repository;
    private ShortUrlsParams $params;
    private ?ApiKey $apiKey;

    public function __construct(ShortUrlRepositoryInterface $repository, ShortUrlsParams $params, ?ApiKey $apiKey)
    {
        $this->repository = $repository;
        $this->params = $params;
        $this->apiKey = $apiKey;
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
            $this->resolveSpec(),
        );
    }

    public function getNbResults(): int
    {
        return $this->repository->countList(
            $this->params->searchTerm(),
            $this->params->tags(),
            $this->params->dateRange(),
            $this->resolveSpec(),
        );
    }

    private function resolveSpec(): ?Specification
    {
        return $this->apiKey !== null ? $this->apiKey->spec() : null;
    }
}
