<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\ShortUrl\Paginator\Adapter;

use Pagerfanta\Adapter\AdapterInterface;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlsParams;
use Shlinkio\Shlink\Core\ShortUrl\Persistence\ShortUrlsCountFiltering;
use Shlinkio\Shlink\Core\ShortUrl\Persistence\ShortUrlsListFiltering;
use Shlinkio\Shlink\Core\ShortUrl\Repository\ShortUrlListRepositoryInterface;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

class ShortUrlRepositoryAdapter implements AdapterInterface
{
    public function __construct(
        private readonly ShortUrlListRepositoryInterface $repository,
        private readonly ShortUrlsParams $params,
        private readonly ?ApiKey $apiKey,
        private readonly string $defaultDomain,
    ) {
    }

    public function getSlice(int $offset, int $length): iterable
    {
        return $this->repository->findList(ShortUrlsListFiltering::fromLimitsAndParams(
            $length,
            $offset,
            $this->params,
            $this->apiKey,
            $this->defaultDomain,
        ));
    }

    public function getNbResults(): int
    {
        return $this->repository->countList(
            ShortUrlsCountFiltering::fromParams($this->params, $this->apiKey, $this->defaultDomain),
        );
    }
}
