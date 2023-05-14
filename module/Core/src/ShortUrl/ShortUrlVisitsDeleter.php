<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\ShortUrl;

use Shlinkio\Shlink\Core\Exception\ShortUrlNotFoundException;
use Shlinkio\Shlink\Core\Model\BulkDeleteResult;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlIdentifier;
use Shlinkio\Shlink\Core\Visit\Repository\VisitDeleterRepositoryInterface;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

class ShortUrlVisitsDeleter implements ShortUrlVisitsDeleterInterface
{
    public function __construct(
        private readonly VisitDeleterRepositoryInterface $repository,
        private readonly ShortUrlResolverInterface $resolver,
    ) {
    }

    /**
     * @throws ShortUrlNotFoundException
     */
    public function deleteShortUrlVisits(ShortUrlIdentifier $identifier, ?ApiKey $apiKey): BulkDeleteResult
    {
        $this->resolver->resolveShortUrl($identifier, $apiKey);
        return new BulkDeleteResult($this->repository->deleteShortUrlVisits($identifier, $apiKey));
    }
}
