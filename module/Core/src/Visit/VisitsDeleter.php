<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Visit;

use Shlinkio\Shlink\Core\Model\BulkDeleteResult;
use Shlinkio\Shlink\Core\Visit\Repository\VisitDeleterRepositoryInterface;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

class VisitsDeleter implements VisitsDeleterInterface
{
    public function __construct(private readonly VisitDeleterRepositoryInterface $repository)
    {
    }

    public function deleteOrphanVisits(?ApiKey $apiKey = null): BulkDeleteResult
    {
        // TODO Check API key has permissions for orphan visits
        return new BulkDeleteResult($this->repository->deleteOrphanVisits());
    }
}
