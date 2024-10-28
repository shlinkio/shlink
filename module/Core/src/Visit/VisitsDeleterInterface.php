<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Visit;

use Shlinkio\Shlink\Core\Model\BulkDeleteResult;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

interface VisitsDeleterInterface
{
    public function deleteOrphanVisits(ApiKey|null $apiKey = null): BulkDeleteResult;
}
