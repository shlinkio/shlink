<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Visit\Repository;

use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlIdentifier;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

interface VisitDeleterRepositoryInterface
{
    public function deleteShortUrlVisits(ShortUrlIdentifier $identifier, ?ApiKey $apiKey): int;
}
