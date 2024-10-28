<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Visit;

use Shlinkio\Shlink\Core\Model\BulkDeleteResult;
use Shlinkio\Shlink\Core\Visit\Repository\VisitDeleterRepositoryInterface;
use Shlinkio\Shlink\Rest\ApiKey\Role;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

class VisitsDeleter implements VisitsDeleterInterface
{
    public function __construct(private readonly VisitDeleterRepositoryInterface $repository)
    {
    }

    public function deleteOrphanVisits(ApiKey|null $apiKey = null): BulkDeleteResult
    {
        $affectedItems = $apiKey?->hasRole(Role::NO_ORPHAN_VISITS) ? 0 : $this->repository->deleteOrphanVisits();
        return new BulkDeleteResult($affectedItems);
    }
}
