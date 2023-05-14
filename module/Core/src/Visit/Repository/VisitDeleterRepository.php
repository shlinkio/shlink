<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Visit\Repository;

use Happyr\DoctrineSpecification\Repository\EntitySpecificationRepository;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlIdentifier;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

class VisitDeleterRepository extends EntitySpecificationRepository implements VisitDeleterRepositoryInterface
{
    public function deleteShortUrlVisits(ShortUrlIdentifier $identifier, ?ApiKey $apiKey): int
    {
        // TODO: Implement deleteShortUrlVisits() method.
        return 0;
    }
}
